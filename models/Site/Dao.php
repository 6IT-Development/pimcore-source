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

namespace Pimcore\Model\Site;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Site;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property Site $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM sites WHERE id = ?', [$id]);
        if (!$data) {
            throw new NotFoundException(sprintf('Unable to load site with ID `%s`', $id));
        }
        $this->assignRowToModel($data);
    }

    /**
     *
     * @throws NotFoundException
     */
    public function getByRootId(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM sites WHERE rootId = ?', [$id]);
        if (!$data) {
            throw new NotFoundException(sprintf('Unable to load site with ID `%s`', $id));
        }
        $this->assignRowToModel($data);
    }

    /**
     * An exact domain wins over a wildcard, the main domain over the domain list and, if several sites claim the
     * same domain, the oldest site. Among the wildcards the most specific one (most literal characters) wins.
     *
     * @throws NotFoundException
     */
    public function getByDomain(string $domain): void
    {
        $domain = Site::normalizeDomain($domain);
        if ($domain === '') {
            // sites without a main domain store '', an empty host must not resolve to any of them
            throw new NotFoundException('there is no site for an empty domain');
        }

        $data = $this->db->fetchAssociative(
            'SELECT * FROM sites WHERE mainDomain = ? OR domains LIKE ? ORDER BY mainDomain = ? DESC, id LIMIT 1',
            [$domain, '%"' . addcslashes($domain, '%_\\') . '"%', $domain]
        );
        if (!$data) {
            $siteId = $this->findSiteIdByWildcard($domain);
            if ($siteId !== null) {
                $data = $this->db->fetchAssociative('SELECT * FROM sites WHERE id = ?', [$siteId]);
            }

            if (!$data) {
                throw new NotFoundException('there is no site for the requested domain: `' . $domain . '´');
            }
        }
        $this->assignRowToModel($data);
    }

    private function findSiteIdByWildcard(string $domain): ?int
    {
        $wildcardDomains = [];
        foreach ($this->db->fetchAllAssociative("SELECT id, domains FROM sites WHERE domains LIKE '%*%' ORDER BY id") as $site) {
            foreach (Site::unserializeList($site['domains']) ?? [] as $siteDomain) {
                if (is_string($siteDomain) && str_contains($siteDomain, '*')) {
                    $siteDomain = str_replace('.*', '*', $siteDomain); // backward compatibility
                    // the first (oldest) site keeps a pattern claimed twice
                    $wildcardDomains[$siteDomain] ??= (int) $site['id'];
                }
            }
        }

        // most literal characters first, so `shop.*.example.com` is tried before `*.example.com`
        uksort($wildcardDomains, static fn (string $a, string $b): int => strlen(str_replace('*', '', $b)) <=> strlen(str_replace('*', '', $a)));

        foreach ($wildcardDomains as $wildcardDomain => $siteId) {
            $pattern = str_replace('\\*', '.*', preg_quote((string) $wildcardDomain, '#'));
            if (preg_match('#^' . $pattern . '$#i', $domain)) {
                return $siteId;
            }
        }

        return null;
    }

    /**
     * The serialized lists are decoded here: an unreadable stored value is logged and loads as empty list instead of
     * making the site unloadable
     */
    private function assignRowToModel(array $data): void
    {
        foreach (['domains', 'localizedErrorDocuments'] as $column) {
            if (!is_string($data[$column] ?? null)) {
                continue;
            }

            $list = Site::unserializeList($data[$column]);
            if ($list === null) {
                Logger::error(sprintf('Site %s: the stored %s are not a serialized array and are ignored', $data['id'], $column));
            }
            $data[$column] = $list ?? [];
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    /**
     * Create a new record for the object in database
     */
    public function create(): void
    {
        $ts = time();
        $this->model->setCreationDate($ts);
        $this->model->setModificationDate($ts);
        $this->db->insert('sites', ['rootId' => $this->model->getRootId()]);
        $this->model->setId((int)$this->db->lastInsertId());
    }

    /**
     * Save changes to database, it's a good idea to use save() instead
     */
    public function update(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);

        $data = [];
        $site = $this->model->getObjectVars();

        foreach ($site as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('sites'))) {
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                }
                if (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('sites', $data, ['id' => $this->model->getId()]);

        $this->model->clearDependentCache();
    }

    /**
     * Deletes site from database
     */
    public function delete(): void
    {
        $this->db->delete('sites', ['id' => $this->model->getId()]);
        //clean slug table
        Model\DataObject\Data\UrlSlug::handleSiteDeleted($this->model->getId());

        $this->model->clearDependentCache();
    }
}
