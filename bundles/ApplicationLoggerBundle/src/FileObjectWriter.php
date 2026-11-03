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

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Stores data in the storage of the application logger and returns the FileObject to pass to a log call
 */
final readonly class FileObjectWriter
{
    public function __construct(
        #[Autowire(service: 'pimcore.application_log.storage')]
        private FilesystemOperator $storage,
        private LoggerInterface    $logger
    )
    {
    }

    /**
     * If the file cannot be written, the returned FileObject has no filename and the log entry no link to a missing file
     */
    public function write(string $data, ?string $filename = null): FileObject
    {
        $filename = $filename ?: date('/Y/m/d/') . 'fileobject_' . Uuid::v7();

        try {
            $this->storage->write($filename, $data);
        } catch (FilesystemException) {
            $this->logger->warning('Application Logger could not write File Object:' . $filename);

            return new FileObject($data, null);
        }

        return new FileObject($data, $filename);
    }
}
