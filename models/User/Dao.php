<?php

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

namespace Pimcore\Model\User;

use Pimcore\Model\User;

/**
 * @internal
 *
 * @property User $model
 */
class Dao extends UserRole\Dao
{
    /**
     * Writes only the password, without validating and saving the whole user (e.g. a rehash on login)
     */
    public function updatePassword(): void
    {
        $this->db->update('users', ['password' => $this->model->getPassword()], ['id' => $this->model->getId()]);
    }

    /**
     * Writes only the password recovery token, without validating and saving the whole user
     */
    public function updatePasswordRecoveryToken(): void
    {
        $this->db->update('users', ['passwordRecoveryToken' => $this->model->getPasswordRecoveryToken()], ['id' => $this->model->getId()]);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        parent::delete();

        $userId = $this->model->getId();

        // cleanup system

        // assets
        $this->db->update('assets', ['userOwner' => null], ['userOwner' => $userId]);
        $this->db->update('assets', ['userModification' => null], ['userModification' => $userId]);

        // documents
        $this->db->update('documents', ['userOwner' => null], ['userOwner' => $userId]);
        $this->db->update('documents', ['userModification' => null], ['userModification' => $userId]);

        // objects
        $this->db->update('objects', ['userOwner' => null], ['userOwner' => $userId]);
        $this->db->update('objects', ['userModification' => null], ['userModification' => $userId]);

        // versions
        $this->db->update('versions', ['userId' => null], ['userId' => $userId]);

        // translations
        $this->db->update('translations_messages', ['userOwner' => null], ['userOwner' => $userId]);
        $this->db->update('translations_messages', ['userModification' => null], ['userModification' => $userId]);

        // notes
        $this->db->update('notes', ['user' => null], ['user' => $userId]);

        // locks of the user would block the elements until they expire
        $this->db->delete('edit_lock', ['userId' => $userId]);

        // grid and import configs: favourites and shares of the user, and the own configs nobody else uses; configs
        // shared with others are kept
        $this->db->delete('gridconfig_favourites', ['ownerId' => $userId]);
        $this->db->delete('gridconfig_shares', ['sharedWithUserId' => $userId]);
        $this->db->delete('importconfig_shares', ['sharedWithUserId' => $userId]);
        $this->db->executeStatement(
            'DELETE FROM gridconfigs WHERE ownerId = ? AND COALESCE(shareGlobally, 0) = 0
                AND NOT EXISTS (SELECT 1 FROM gridconfig_shares s WHERE s.gridConfigId = gridconfigs.id)',
            [$userId]
        );
        $this->db->executeStatement(
            'DELETE FROM importconfigs WHERE ownerId = ? AND COALESCE(shareGlobally, 0) = 0
                AND NOT EXISTS (SELECT 1 FROM importconfig_shares s WHERE s.importConfigId = importconfigs.id)',
            [$userId]
        );
    }
}
