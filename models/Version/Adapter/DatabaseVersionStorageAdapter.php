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

namespace Pimcore\Model\Version\Adapter;

use Doctrine\DBAL;
use Doctrine\DBAL\Exception;
use Pimcore\Model\Version;

/**
 * @internal
 */
class DatabaseVersionStorageAdapter implements VersionStorageAdapterInterface
{
    public const string versionsTableName = 'versionsData';

    public function __construct(protected DBAL\Connection $databaseConnection)
    {
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        if (isset($binaryDataStream) === true &&
            empty($version->getBinaryFileId()) === true) {
            $contents = stream_get_contents($binaryDataStream);
        }

        // a re-saved version (or one that references its own row as binaryFileId) has no binary to write:
        // keep the stored one, other versions may read it through binaryFileId
        $query = 'INSERT INTO ' . self::versionsTableName . '(`id`, `cid`, `ctype`, `metaData`, `binaryData`) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE `metaData` = ?, `binaryData` = COALESCE(?, `binaryData`)';

        $this->databaseConnection->executeStatement(
            $query,
            [
                $version->getId(),
                $version->getCid(),
                $version->getCtype(),
                $metaData,
                $contents ?? null,
                $metaData,
                $contents ?? null,
            ]
        );
    }

    /**
     *
     *
     * @throws Exception
     */
    protected function loadData(int    $id,
                                int    $cId,
                                string $cType,
                                bool   $binaryData = false): mixed
    {
        $dataColumn = $binaryData ? 'binaryData' : 'metaData';

        return $this->databaseConnection->fetchOne('SELECT ' . $dataColumn . ' FROM ' . self::versionsTableName . ' WHERE id = :id AND cid = :cid and ctype = :ctype',
            [
                'id' => $id,
                'cid' => $cId,
                'ctype' => $cType,
            ]);
    }

    public function loadMetaData(Version $version): ?string
    {
        $metaData = $this->loadData($version->getId(), $version->getCid(), $version->getCtype());

        // false: no row, null: no data
        return is_string($metaData) ? $metaData : null;
    }

    /**
     * @return resource|null
     */
    protected function getStream(mixed $data): mixed
    {
        if (is_string($data) && $data !== '') {
            $stream = tmpfile();
            fwrite($stream, $data);
            rewind($stream);

            return $stream;
        }

        return null;
    }

    public function loadBinaryData(Version $version): mixed
    {
        $binaryData = $this->loadData($version->getBinaryFileId() ?? $version->getId(), $version->getCid(), $version->getCtype(), true);

        return $this->getStream($binaryData);
    }

    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $identifier = [
            'id' => $version->getId(),
            'cid' => $version->getCid(),
            'ctype' => $version->getCtype(),
        ];

        if ($isBinaryHashInUse) {
            // later versions read the binary from this row through binaryFileId: keep it, drop only the metadata
            $this->databaseConnection->executeStatement(
                'UPDATE ' . self::versionsTableName . ' SET metaData = NULL WHERE id = :id AND cid = :cid AND ctype = :ctype AND binaryData IS NOT NULL',
                $identifier
            );
            $this->databaseConnection->executeStatement(
                'DELETE FROM ' . self::versionsTableName . ' WHERE id = :id AND cid = :cid AND ctype = :ctype AND binaryData IS NULL',
                $identifier
            );

            return;
        }

        $this->databaseConnection->delete(self::versionsTableName, $identifier);

        // the last version using a binary kept from an already deleted version: remove that row as well
        $binaryFileId = $version->getBinaryFileId();
        if ($binaryFileId && $binaryFileId !== $version->getId()) {
            $this->databaseConnection->executeStatement(
                'DELETE FROM ' . self::versionsTableName . ' WHERE id = :id AND cid = :cid AND ctype = :ctype AND metaData IS NULL',
                ['id' => $binaryFileId] + $identifier
            );
        }
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->loadBinaryData($version);
    }

    public function getFileStream(Version $version): mixed
    {
        $metaData = $this->loadMetaData($version);

        return $this->getStream($metaData);
    }

    public function getStorageType(?int $metaDataSize = null,
                                   ?int $binaryDataSize = null): string
    {
        return 'db';
    }
}
