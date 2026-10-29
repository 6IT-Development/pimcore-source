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

namespace Pimcore\Model\Asset\WebDAV;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Model\User;
use Pimcore\Tool\Admin;
use Sabre\DAV;
use Throwable;

/**
 * @internal
 */
class Tree extends DAV\Tree
{
    /**
     * Moves a file/directory
     *
     * @param string $sourcePath
     * @param string $destinationPath
     *
     * @throws DAV\Exception\Forbidden
     * @throws DAV\Exception\NotFound
     * @throws DAV\Exception\Conflict
     * @throws DAV\Exception
     */
    public function move($sourcePath, $destinationPath): void
    {
        $user = Admin::getCurrentUser();
        if (!$user) {
            throw new DAV\Exception\Forbidden('Missing user');
        }

        $nameParts = explode('/', $sourcePath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $sourcePath = implode('/', $nameParts);

        $nameParts = explode('/', $destinationPath);
        $nameParts[count($nameParts) - 1] = Element\Service::getValidKey($nameParts[count($nameParts) - 1], 'asset');
        $destinationPath = implode('/', $nameParts);

        $sourceAsset = Asset::getByPath('/' . $sourcePath);
        if (!$sourceAsset) {
            throw new DAV\Exception\NotFound('File not found: ' . $sourcePath);
        }
        $this->assertAllowed($sourceAsset, 'view', $user);

        // the source is only deleted after the overwritten target was saved
        $sourceToDelete = null;

        try {
            if (dirname($sourcePath) == dirname($destinationPath)) {
                if ($asset = Asset::getByPath('/' . $destinationPath)) {
                    // If we got here, this means the destination exists, and needs to be overwritten
                    $this->assertAllowed($asset, 'publish', $user);
                    $this->assertAllowed($sourceAsset, 'delete', $user);

                    $asset->setData($sourceAsset->getData());
                    $sourceToDelete = $sourceAsset;
                } elseif ($asset = Asset\WebDAV\Service::getFromDeleteLog('/' . $destinationPath)) {
                    // see: Asset\WebDAV\File::delete() why this is necessary
                    // the deleted asset is restored with the data of the source asset
                    $this->assertAllowed($sourceAsset->getParent(), 'create', $user);
                    $this->assertAllowed($sourceAsset, 'delete', $user);

                    $asset->setData($sourceAsset->getData());
                    $sourceToDelete = $sourceAsset;
                } else {
                    $this->assertAllowed($sourceAsset, 'rename', $user);

                    $asset = $sourceAsset;
                }

                $asset->setFilename(basename($destinationPath));
            } else {
                $parent = Asset::getByPath('/' . dirname($destinationPath));
                if (!$parent) {
                    throw new DAV\Exception\Conflict('Destination folder not found: ' . dirname($destinationPath));
                }
                $this->assertAllowed($parent, 'create', $user);
                $this->assertAllowed($sourceAsset, 'delete', $user);

                $asset = $sourceAsset;
                $asset->setPath($parent->getRealFullPath() . '/');
                $asset->setParentId($parent->getId());
            }

            $asset->setUserModification($user->getId());

            $db = Db::get();
            $db->beginTransaction();

            try {
                $asset->save();
                $sourceToDelete?->delete();
                $db->commit();
            } catch (Throwable $e) {
                $db->rollBack();

                throw $e;
            }
        } catch (DAV\Exception $e) {
            throw $e;
        } catch (Element\DuplicateFullPathException $e) {
            Logger::error((string)$e);

            throw new DAV\Exception\Conflict('Destination already exists: ' . $destinationPath);
        } catch (Throwable $e) {
            // Sabre answers 201/204 unless move() throws, so a failure must not be swallowed
            Logger::error((string)$e);

            throw new DAV\Exception('Unable to move ' . $sourcePath . ' to ' . $destinationPath);
        }
    }

    /**
     * @throws DAV\Exception\Forbidden
     */
    private function assertAllowed(?Asset $asset, string $permission, User $user): void
    {
        if (!$asset || !$asset->isAllowed($permission, $user)) {
            throw new DAV\Exception\Forbidden(sprintf('Missing "%s" permission', $permission));
        }
    }
}
