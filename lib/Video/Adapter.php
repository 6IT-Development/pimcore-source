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

namespace Pimcore\Video;

use Closure;

/**
 * @internal
 */
abstract class Adapter
{
    public int $videoBitrate;

    public int $audioBitrate;

    public string $format;

    public array $medias;

    public string $destinationFile;

    public string $storageFile;

    /**
     * length in seconds
     *
     */
    public int $length;

    /**
     * Called while a conversion runs, e.g. to keep a lock alive; set in the worker, not stored with the job
     */
    private ?Closure $progressCallback = null;

    public function setAudioBitrate(int $audioBitrate): static
    {
        $this->audioBitrate = $audioBitrate;

        return $this;
    }

    public function getAudioBitrate(): int
    {
        return $this->audioBitrate;
    }

    public function setVideoBitrate(int $videoBitrate): static
    {
        $this->videoBitrate = $videoBitrate;

        return $this;
    }

    public function getVideoBitrate(): int
    {
        return $this->videoBitrate;
    }

    public function setProgressCallback(?Closure $progressCallback): static
    {
        $this->progressCallback = $progressCallback;

        return $this;
    }

    protected function reportProgress(): void
    {
        if ($this->progressCallback) {
            ($this->progressCallback)();
        }
    }

    abstract public function load(string $file, array $options = []): static;

    abstract public function save(): bool;

    abstract public function saveImage(string $file, ?int $timeOffset = null): bool;

    abstract public function destroy(): void;

    public function getMedias(): ?array
    {
        return $this->medias;
    }

    public function setMedias(?array $medias): void
    {
        $this->medias = $medias;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setDestinationFile(string $destinationFile): static
    {
        $this->destinationFile = $destinationFile;

        return $this;
    }

    public function getDestinationFile(): string
    {
        return $this->destinationFile;
    }

    public function setLength(int $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getStorageFile(): string
    {
        return $this->storageFile;
    }

    public function setStorageFile(string $storageFile): void
    {
        $this->storageFile = $storageFile;
    }

    abstract public function getDuration(): ?float;

    abstract public function getDimensions(): ?array;
}
