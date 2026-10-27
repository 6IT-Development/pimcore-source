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

namespace Pimcore\Document;

use Exception;
use League\Flysystem\FilesystemException;
use Pimcore;
use Pimcore\Config;
use Pimcore\Document\Renderer\DocumentRendererInterface;
use Pimcore\Http\Request\Resolver\StaticPageResolver;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tool\Storage;
use Symfony\Component\Lock\LockFactory;

class StaticPageGenerator
{
    public function __construct(
        protected DocumentRendererInterface $documentRenderer,
        private readonly LockFactory        $lockFactory,
        protected SystemSettingsConfig      $settingsConfig
    )
    {
    }

    public function getStoragePath(Document\PageSnippet $document): string
    {
        if ($document instanceof Document\Page && $document->getPrettyUrl()) {
            return $this->getStoragePathForPath($document->getPrettyUrl());
        }

        $path = $document->getRealFullPath();

        return $this->getStoragePathForPath($path === '/' ? '/%home' : $path);
    }

    private function getStoragePathForPath(string $path): string
    {
        $useMainDomain = Config::getSystemConfiguration('documents')['static_page_generator']['use_main_domain'];
        if ($useMainDomain) {
            $systemConfig = $this->settingsConfig->getSystemSettingsConfig();
            $mainDomain = '/' . $systemConfig['general']['domain'];
            $returnPath = '';
            $pathInfo = pathinfo($path);
            if ($pathInfo['dirname'] != '') {
                $directories = explode('/', $pathInfo['dirname']);
                $directories = array_filter($directories);
                $pathString = '';
                foreach ($directories as $directory) {
                    $pathString .= '/' . $directory;
                    // the directory does not exist anymore when the path is the old path of a moved document
                    $doc = Document::getByPath($pathString);
                    $site = $doc ? Site::getByRootId($doc->getId()) : null;
                    if ($site instanceof Site) {
                        $mainDomain = '/' . $site->getMainDomain();
                    } else {
                        $returnPath .= '/' . $directory;
                    }
                }
                $returnPath .= '/' . $pathInfo['basename'];
            }

            return $mainDomain . $returnPath . '.html';
        }

        return $path . '.html';
    }

    public function generate(Document\PageSnippet $document, array $params = []): bool
    {
        $storagePath = $this->getStoragePath($document);

        $storage = Storage::get('document_static');
        $startTime = microtime(true);

        $lockKey = 'document_static_' . $document->getId() . '_' . md5($storagePath);

        $lock = $this->lockFactory->createLock($lockKey);

        if ($params['is_cli'] ?? false) {
            $lock->acquire(true);
        }

        try {
            if (!$response = $params['response'] ?? false) {
                $response = $this->documentRenderer->render($document, [
                    'pimcore_static_page_generator' => true,
                    StaticPageResolver::ATTRIBUTE_PIMCORE_STATIC_PAGE => true,
                ]);
            }

            $storage->write($storagePath, $response);
        } catch (Exception $e) {
            Logger::debug('Error generating static Page ' . $storagePath . ': ' . $e->getMessage());

            return false;
        }

        Logger::debug('Static Page ' . $storagePath . ' generated in ' . (microtime(true) - $startTime) . ' seconds');

        if ($params['is_cli'] ?? false) {
            $lock->release();
            Pimcore::getKernel()->getContainer()->get('services_resetter')->reset();
        }

        return true;
    }

    /**
     *
     * @throws FilesystemException
     */
    public function remove(Document\PageSnippet $document): void
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        $storage->delete($storagePath);
    }

    /**
     * Removes the static page stored for $path and the static pages of all documents below it,
     * e.g. for the old path of a moved/renamed document or for a deleted document.
     * Pages with a pretty URL are stored under that URL and are not affected.
     *
     * @throws FilesystemException
     */
    public function removeTree(string $path): void
    {
        if ($path === '' || $path === '/') {
            return;
        }

        $storagePath = $this->getStoragePathForPath(rtrim($path, '/'));
        $storage = Storage::get('document_static');

        $storage->delete($storagePath);
        $storage->deleteDirectory(substr($storagePath, 0, -strlen('.html')));
    }

    public function pageExists(Document\PageSnippet $document): bool
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        return $storage->fileExists($storagePath);
    }

    public function getLastModified(Document\PageSnippet $document): ?int
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        if ($storage->fileExists($storagePath)) {
            return $storage->lastModified($storagePath);
        }

        return null;
    }
}
