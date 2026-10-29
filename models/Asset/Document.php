<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license GPLv3 and PCL
 */

namespace Pimcore\Model\Asset;

use Exception;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Logger;
use Pimcore\Model;
use Throwable;

/**
 * @method Dao getDao()
 */
class Document extends Model\Asset
{
    public const string CUSTOM_SETTING_PDF_SCAN_STATUS = 'document_pdf_scan_status';

    private const int PDF_SCAN_CHUNK_SIZE = 65536;

    private const int PDF_SCAN_OVERLAP = 64;

    private const int PDF_SCAN_MAX_OBJECT_STREAM_SIZE = 16777216;

    protected string $type = 'document';

    protected function update(array $params = []): void
    {
        if ($this->getDataChanged()) {
            $this->removeCustomSetting('document_page_count');
        }

        parent::update($params);

        if ($params['isUpdate']) {
            $this->clearThumbnails();
        }
    }

    /**
     *
     * @internal
     */
    public function processPageCount(?string $path = null): bool
    {
        if (!$this->isPageCountProcessingEnabled()) {
            return false;
        }

        if (!\Pimcore\Document::isAvailable()) {
            Logger::error(
                sprintf(
                    "Couldn't create image-thumbnail of document %s as no document adapter is available",
                    $this->getRealFullPath()
                )
            );

            return false;
        }

        try {
            $converter = \Pimcore\Document::getInstance();
            $converter->load($this);

            // read from blob here, because in $this->update() $this->getFileSystemPath() contains the old data
            $pageCount = $converter->getPageCount();
            $this->setCustomSetting('document_page_count', $pageCount);
        } catch (Exception $e) {
            Logger::error((string)$e);
            $this->setCustomSetting('document_page_count', 'failed');

            return false;
        }

        return true;
    }

    /**
     * returns null when page count wasn't processed yet (done asynchronously)
     *
     */
    public function getPageCount(): ?int
    {
        $pageCount = $this->getCustomSetting('document_page_count');
        if ($pageCount === null || $pageCount === '') {
            return null;
        }

        return (int)$this->getCustomSetting('document_page_count');
    }

    /**
     * @param bool $deferred $deferred deferred means that the image will be generated on-the-fly (details see below)
     */
    public function getImageThumbnail(
        array|string|Image\Thumbnail\Config $thumbnailName,
        int                                 $page = 1,
        bool                                $deferred = false
    ): Document\ImageThumbnailInterface
    {
        if (!$this->isThumbnailsEnabled() || !\Pimcore\Document::isAvailable()) {
            return new Document\ImageThumbnail(null);
        }

        return new Document\ImageThumbnail($this, $thumbnailName, $page, $deferred);
    }

    /**
     * @throws Exception
     */
    public function getText(?int $page = null): ?string
    {
        if (!$this->isTextProcessingEnabled()) {
            return null;
        }

        if (!\Pimcore\Document::isAvailable() || !\Pimcore\Document::isFileTypeSupported($this->getFilename())) {
            return null;
        }

        $cacheKey = 'asset_document_text_' . $this->getId() . '_' . ($page ?: 'all');
        if (!$text = Cache::load($cacheKey)) {
            $document = \Pimcore\Document::getInstance();
            $text = $document->getText($page, $this);
            Cache::save($text, $cacheKey, $this->getCacheTags(), null, 99, true);
        }

        return (string)$text;
    }

    /**
     * Scans the PDF and stores the result as scan status.
     *
     * @return bool true if the PDF contains JavaScript or could not be verified (status UNSAFE)
     */
    public function checkIfPdfContainsJS(): bool
    {
        return $this->scanPdfForJavaScript() === Model\Asset\Enum\PdfScanStatus::UNSAFE;
    }

    /**
     * Scans the PDF and stores the result as scan status.
     *
     * @return Model\Asset\Enum\PdfScanStatus|null null if PDF scanning is disabled
     */
    public function scanPdfForJavaScript(): ?Model\Asset\Enum\PdfScanStatus
    {
        if (!$this->isPdfScanningEnabled()) {
            return null;
        }

        $this->setCustomSetting(
            self::CUSTOM_SETTING_PDF_SCAN_STATUS,
            Model\Asset\Enum\PdfScanStatus::IN_PROGRESS->value
        );

        try {
            $status = $this->pdfMayContainJavaScript()
                ? Model\Asset\Enum\PdfScanStatus::UNSAFE
                : Model\Asset\Enum\PdfScanStatus::SAFE;
        } catch (Throwable $e) {
            Logger::warning('Unable to scan PDF asset ' . $this->getId() . ' for JavaScript: ' . $e->getMessage());
            $status = Model\Asset\Enum\PdfScanStatus::UNSAFE;
        }

        $this->setCustomSetting(self::CUSTOM_SETTING_PDF_SCAN_STATUS, $status->value);

        return $status;
    }

    public function getScanStatus(): ?Model\Asset\Enum\PdfScanStatus
    {
        if ($scanStatus = $this->getCustomSetting(self::CUSTOM_SETTING_PDF_SCAN_STATUS)) {
            return Model\Asset\Enum\PdfScanStatus::tryFrom($scanStatus);
        }

        return null;
    }

    /**
     * @internal
     */
    public function isThumbnailsEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['thumbnails']['enabled'];
    }

    /**
     * @internal
     */
    public function isPageCountProcessingEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['process_page_count'];
    }

    private function isTextProcessingEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['process_text'];
    }

    private function isPdfScanningEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['scan_pdf'];
    }

    /**
     * Streams through the file and looks for JavaScript names (also with #xx name escapes and across chunk
     * boundaries). Compressed object streams are inflated and scanned as well; an object stream that can't be
     * decoded counts as JavaScript, because its content can't be verified.
     */
    private function pdfMayContainJavaScript(): bool
    {
        $filePointer = $this->getStream();
        $buffer = '';
        $objectStream = null; // the collected data while inside a compressed object stream

        try {
            do {
                $chunk = fread($filePointer, self::PDF_SCAN_CHUNK_SIZE);
                $eof = $chunk === false || $chunk === '';
                if (!$eof) {
                    $buffer .= $chunk;
                }

                while (true) {
                    if ($objectStream === null) {
                        if ($this->containsPdfJavaScriptName($buffer)) {
                            return true;
                        }

                        $objectStreamPosition = strpos($buffer, '/ObjStm');
                        if ($objectStreamPosition === false) {
                            // keep an overlap, a name can be split between two chunks
                            $buffer = substr($buffer, -self::PDF_SCAN_OVERLAP);

                            break;
                        }

                        $streamPosition = strpos($buffer, 'stream', $objectStreamPosition);
                        if ($streamPosition === false) {
                            if ($eof || strlen($buffer) > self::PDF_SCAN_MAX_OBJECT_STREAM_SIZE) {
                                return true;
                            }
                            $buffer = substr($buffer, $objectStreamPosition);

                            break;
                        }

                        $objectStream = '';
                        $buffer = substr($buffer, $streamPosition + strlen('stream'));
                        $buffer = preg_replace('/^\r?\n/', '', $buffer);
                    } else {
                        $endPosition = strpos($buffer, 'endstream');
                        if ($endPosition === false) {
                            if ($eof) {
                                return true;
                            }
                            // keep the tail, the keyword can be split between two chunks
                            $keep = strlen('endstream');
                            $objectStream .= substr($buffer, 0, -$keep);
                            $buffer = substr($buffer, -$keep);
                            if (strlen($objectStream) > self::PDF_SCAN_MAX_OBJECT_STREAM_SIZE) {
                                return true;
                            }

                            break;
                        }

                        $objectStream .= substr($buffer, 0, $endPosition);
                        $buffer = substr($buffer, $endPosition + strlen('endstream'));

                        $decoded = @zlib_decode($objectStream);
                        $objectStream = null;
                        if ($decoded === false || $this->containsPdfJavaScriptName($decoded)) {
                            return true;
                        }
                    }
                }
            } while (!$eof);
        } finally {
            if (is_resource($filePointer)) {
                fclose($filePointer);
            }
        }

        return false;
    }

    private function containsPdfJavaScriptName(string $data): bool
    {
        // PDF names may escape any character as #xx, e.g. /J#53
        $data = preg_replace_callback(
            '/#([0-9A-Fa-f]{2})/',
            static fn (array $matches): string => chr((int) hexdec($matches[1])),
            $data
        );

        return str_contains($data, '/JS') || str_contains($data, '/JavaScript');
    }
}
