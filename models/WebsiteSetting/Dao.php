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

namespace Pimcore\Model\WebsiteSetting;

use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\WebsiteSetting;

/**
 * @internal
 *
 * @property WebsiteSetting $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @throws NotFoundException
     */
    public function getById(?int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM website_settings WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException('Website Setting with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     * @throws NotFoundException
     */
    public function getByName(?string $name = null, ?int $siteId = null, ?string $language = null, ?string $fallbackLanguage = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }
        $rows = $this->db->fetchAllAssociative(
            "SELECT *
            FROM website_settings
            WHERE name = ?
                AND (
                    siteId IS NULL
                    OR siteId = 0
                    OR siteId = ?
                )
                AND (
                    language IS NULL
                    OR language = ''
                    OR language = ?
                    OR language = ?
                )
            ORDER BY id",
            [$this->model->getName(), $siteId, $language, $fallbackLanguage]
        );

        // rank in PHP with the same rule as Config::getWebsiteConfig(), the first row wins a tie
        $data = null;
        $bestRank = null;
        foreach ($rows as $row) {
            $rank = WebsiteSetting::getOverrideRank(
                $row['siteId'] === null ? null : (int)$row['siteId'],
                (string)$row['language'],
                $siteId,
                $language,
                $fallbackLanguage
            );
            if ($rank !== null && ($bestRank === null || $rank > $bestRank)) {
                $data = $row;
                $bestRank = $rank;
            }
        }

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException('Website Setting with name: ' . $this->model->getName() . ' does not exist');
        }
    }

    /**
     * Whether another setting has the same name, site and language; no site is stored as NULL or the legacy 0.
     */
    public function hasDuplicate(): bool
    {
        return (bool)$this->db->fetchOne(
            'SELECT id FROM website_settings WHERE name = ? AND IFNULL(siteId, 0) = ? AND language = ? AND id != ? LIMIT 1',
            [$this->model->getName(), (int)$this->model->getSiteId(), $this->model->getLanguage(), (int)$this->model->getId()]
        );
    }

    public function save(): void
    {
        if ($this->model->getId()) {
            $this->update();
        } else {
            $this->create();
        }
    }

    public function delete(): void
    {
        $this->db->delete('website_settings', ['id' => $this->model->getId()]);
        $this->model->clearDependentCache();
    }

    /**
     * @throws NotFoundException
     */
    public function update(): void
    {
        $this->model->setModificationDate(time());

        $affectedRows = $this->db->update('website_settings', array_filter($this->model->getObjectVars(), fn($key) => in_array($key, $this->getValidTableColumns('website_settings'), true), ARRAY_FILTER_USE_KEY), ['id' => $this->model->getId()]);

        // 0 affected rows can also mean unchanged values, unless the client flag for found rows is set
        if ($affectedRows === 0
            && !$this->db->fetchOne('SELECT id FROM website_settings WHERE id = ?', [$this->model->getId()])
        ) {
            throw new NotFoundException('Website Setting with id: ' . $this->model->getId() . ' does not exist');
        }

        $this->model->clearDependentCache();
    }

    public function create(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert('website_settings', ['name' => $this->model->getName(), 'siteId' => $this->model->getSiteId()]);

        $this->model->setId((int)$this->db->lastInsertId());

        $this->update();
    }
}
