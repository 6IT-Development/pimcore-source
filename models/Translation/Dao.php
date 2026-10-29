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

namespace Pimcore\Model\Translation;

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Translation;
use Pimcore\Model\User;
use Pimcore\Tool\Admin;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * @internal
 *
 * @property Translation $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @var string
     */
    public const string TABLE_PREFIX = 'translations_';

    public function getDatabaseTableName(): string
    {
        return self::TABLE_PREFIX . $this->model->getDomain();
    }

    /**
     *
     * @throws NotFoundResourceException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getByKey(string $key, ?array $languages = null): void
    {
        $table = $this->db->quoteIdentifier($this->getDatabaseTableName());
        if (is_array($languages)) {
            $sql = 'SELECT * FROM ' . $table . ' WHERE `key` = :key
            AND `language` IN (:languages) ORDER BY `creationDate` ';
        } else {
            $sql = 'SELECT * FROM ' . $table . ' WHERE `key` = :key ORDER BY `creationDate` ';
        }

        $data = $this->db->fetchAllAssociative($sql,
            ['key' => $key, 'languages' => $languages],
            ['languages' => ArrayParameterType::STRING]
        );

        if (!empty($data)) {
            foreach ($data as $d) {
                $this->model->addTranslation($d['language'], $d['text'] ?? '');
                $this->model->setKey($d['key']);
                $this->model->setCreationDate($d['creationDate']);
                $this->model->setModificationDate($d['modificationDate']);
                $this->model->setType($d['type']);
                $this->model->setUserOwner($d['userOwner']);
                $this->model->setUserModification($d['userModification']);
            }
        } else {
            throw new NotFoundResourceException("Translation-Key -->'" . $key . "'<-- not found");
        }
    }

    /**
     * Save object to database
     *
     * @param bool $onlyMissing insert only the languages that have no row yet, never overwrite existing texts
     */
    public function save(bool $onlyMissing = false): void
    {
        //Create Domain table if doesn't exist
        $this->createOrUpdateTable();

        $this->updateModificationInfos();
        $sanitizer = $this->model->getTranslationSanitizer();

        $user = null;

        // null means no restriction, an empty array means the user may edit no language at all
        $editableLanguages = null;
        if ($this->model->getDomain() != Translation::DOMAIN_ADMIN) {
            if ($user = User::getById($this->model->getUserModification())) {
                $editableLanguages = $user->getAllowedLanguagesForEditingWebsiteTranslations();
            }
        }

        if ($this->model->getKey() !== '') {
            foreach ($this->model->getTranslations() as $language => $text) {
                // setTranslations() does not type its values, never write a NULL (it breaks the catalogue)
                $text = (string)$text;

                if ($editableLanguages !== null && !in_array($language, $editableLanguages, true)) {
                    Logger::warning(sprintf('User %s not allowed to edit %s translation', $user?->getUsername() ?? 'N/A', $language)); // @phpstan-ignore-line

                    continue;
                }

                if ($text != strip_tags($text)) {
                    $text = $sanitizer->sanitizeFor('body', $text);
                    $this->model->addTranslation($language, $text);
                }

                $data = [
                    'key' => $this->model->getKey(),
                    'type' => $this->model->getType(),
                    'language' => $language,
                    'text' => $text,
                    'modificationDate' => $this->model->getModificationDate(),
                    'creationDate' => $this->model->getCreationDate(),
                    'userOwner' => $this->model->getUserOwner(),
                    'userModification' => $this->model->getUserModification(),
                ];
                if ($onlyMissing) {
                    $this->insertIfMissing($data);
                } else {
                    Helper::upsert($this->db, $this->getDatabaseTableName(), $data, $this->getPrimaryKey($this->getDatabaseTableName()));
                }
            }
        }
    }

    private function insertIfMissing(array $data): void
    {
        $columns = array_map(fn (string $column) => $this->db->quoteIdentifier($column), array_keys($data));

        // a no-op update on duplicate keys (unlike INSERT IGNORE, other errors are still reported)
        $this->db->executeStatement(
            'INSERT INTO ' . $this->db->quoteIdentifier($this->getDatabaseTableName())
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')'
            . ' ON DUPLICATE KEY UPDATE `key` = `key`',
            array_values($data)
        );
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete($this->db->quoteIdentifier($this->getDatabaseTableName()), [$this->db->quoteIdentifier('key') => $this->model->getKey()]);
    }

    /**
     * Returns a array containing all available languages
     *
     */
    public function getAvailableLanguages(): array
    {
        $l = $this->db->fetchAllAssociative('SELECT `language` FROM ' . $this->db->quoteIdentifier($this->getDatabaseTableName()) . ' GROUP BY `language`;');
        $languages = [];

        foreach ($l as $values) {
            $languages[] = $values['language'];
        }

        return $languages;
    }

    /**
     * Returns a array containing all available (registered) domains
     *
     */
    public function getAvailableDomains(): array
    {
        $domainTables = $this->db->fetchAllAssociative("SHOW TABLES LIKE 'translations_%'");
        $domains = [];

        foreach ($domainTables as $domainTable) {
            $domain = str_replace('translations_', '', $domainTable[array_key_first($domainTable)]);
            if ($this->isAValidDomain($domain)) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    /**
     * Returns boolean, if the domain table exists & domain registered in config
     *
     *
     */
    public function isAValidDomain(string $domain): bool
    {
        try {
            if (!preg_match(Translation::DOMAIN_PATTERN, $domain)
                || !in_array($domain, Translation::getRegisteredDomains(), true)) {
                return false;
            }

            $this->db->fetchOne('SELECT 1 FROM ' . $this->db->quoteIdentifier(self::TABLE_PREFIX . $domain) . ' LIMIT 1;');

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function createOrUpdateTable(): void
    {
        $table = $this->getDatabaseTableName();

        // only registered domains with a plain identifier name get a table
        Translation::validateDomain($this->model->getDomain());

        $this->db->executeQuery('CREATE TABLE IF NOT EXISTS ' . $this->db->quoteIdentifier($table) . " (
                          `key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
                          `type` varchar(10) DEFAULT NULL,
                          `language` varchar(10) NOT NULL DEFAULT '',
                          `text` text DEFAULT NULL,
                          `creationDate` int(11) unsigned DEFAULT NULL,
                          `modificationDate` int(11) unsigned DEFAULT NULL,
                          `userOwner` int(11) unsigned DEFAULT NULL,
                          `userModification` int(11) unsigned DEFAULT NULL,
                          PRIMARY KEY (`key`,`language`),
                          KEY `language` (`language`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    protected function updateModificationInfos(): void
    {
        $updateTime = time();
        $this->model->setModificationDate($updateTime);

        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($updateTime);
        }

        // auto assign user if possible, if no user present, use ID=0 which represents the "system" user
        $userId = Admin::getCurrentUser()?->getId() ?? 0;
        $this->model->setUserModification($userId);

        if ($this->model->getUserOwner() === null) {
            $this->model->setUserOwner($userId);
        }
    }
}
