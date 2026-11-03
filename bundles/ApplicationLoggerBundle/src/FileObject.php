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

namespace Pimcore\Bundle\ApplicationLoggerBundle;

use const PIMCORE_PROJECT_ROOT;

/**
 * Reference to data stored by FileObjectWriter
 */
final readonly class FileObject
{
    /**
     * @param string|null $filename null if the file could not be written
     */
    public function __construct(
        private string  $data,
        private ?string $filename
    )
    {
    }

    #[\Deprecated(message: 'Use getFilename() instead')]
    public function getSystemPath(): ?string
    {
        return $this->filename;
    }

    /**
     * Returns null if the file could not be written
     */
    public function getFilename(): ?string
    {
        if (null === $this->filename) {
            return null;
        }

        return preg_replace('/^' . preg_quote(PIMCORE_PROJECT_ROOT, '/') . '/', '', $this->filename);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return (string)$this->getFilename();
    }
}
