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

namespace Pimcore\Model\Asset\Thumbnail;

use League\Flysystem\StorageAttributes;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
trait ClearTempFilesTrait
{
    /**
     * The prefix of the thumbnail directories generated for this config type, e.g. `image` for `image-thumb__…`.
     */
    abstract protected function getTempFilesPrefix(): string;

    public function clearTempFiles(): void
    {
        $storage = Storage::get('thumbnail');
        $pattern = '@' . preg_quote($this->getTempFilesPrefix(), '@') . '-thumb__[\d]+__' . preg_quote($this->getName(), '@') . '(?:_auto_.+)?$@';
        $contents = $storage->listContents('/', true)->filter(function (StorageAttributes $item) use ($pattern) {
            return $item->isDir() && preg_match($pattern, $item->path());
        })->map(fn(StorageAttributes $attributes) => $attributes->path())->toArray();

        foreach ($contents as $item) {
            $storage->deleteDirectory($item);
        }
    }
}
