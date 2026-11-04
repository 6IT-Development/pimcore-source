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
use Doctrine\DBAL\Exception\TableNotFoundException;
use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Translation;
use Pimcore\Model\User;
use Pimcore\Tool\Admin;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @throws NotFoundException
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

        if (empty($data)) {
            throw new NotFoundException("Translation-Key -->'" . $key . "'<-- not found");
        }

        $this->assignRows($data);
    }

    /**
     * Sets the texts and the key-level fields of the model from the rows of one key
     *
     * @param non-empty-list<array<string, mixed>> $data
     *
     * @internal
     */
    public function assignRows(array $data): void
    {
        // the key-level fields come from one row each, not from whichever language was loaded last: the language
        // created first owns the key, the latest change names the type and the modifying user
        $created = null;
        $modified = null;
        foreach ($data as $d) {
            $this->model->addTranslation($d['language'], $d['text'] ?? '');

            if ($d['creationDate'] !== null && ($created === null || $d['creationDate'] < $created['creationDate'])) {
                $created = $d;
            }
            if ($d['modificationDate'] !== null && ($modified === null || $d['modificationDate'] > $modified['modificationDate'])) {
                $modified = $d;
            }
        }
        $created ??= $data[0];
        $modified ??= $data[array_key_last($data)];

        $this->model->setKey($data[0]['key']);
        $this->model->setCreationDate(self::toNullableInt($created['creationDate']));
        $this->model->setModificationDate(self::toNullableInt($modified['modificationDate']));
        $this->model->setType($modified['type']);
        $this->model->setUserOwner(self::toNullableInt($created['userOwner']));
        $this->model->setUserModification(self::toNullableInt($modified['userModification']));
    }

    private static function toNullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int)$value;
    }

    /**
     * Save object to database
     *
     * @param bool $onlyMissing insert only the languages that have no row yet, never overwrite existing texts
     *
     * @throws AccessDeniedException if the modifying user changed a language they may not edit
     * @throws TableNotFoundException if the domain table is missing and the caller has a transaction open
     */
    public function save(bool $onlyMissing = false): void
    {
        $this->updateModificationInfos();
        $sanitizer = $this->model->getTranslationSanitizer();

        $user = null;

        // null means no restriction, an empty array means the user may edit no language at all. Missing languages are
        // only added by the auto-create of the Translator, a system write that the current editor did not make.
        $editableLanguages = null;
        if (!$onlyMissing && $this->model->getDomain() != Translation::DOMAIN_ADMIN) {
            if ($user = User::getById($this->model->getUserModification())) {
                $editableLanguages = $user->getAllowedLanguagesForEditingWebsiteTranslations();
            }
        }

        $rows = [];
        foreach ($this->model->getTranslations() as $language => $text) {
            // setTranslations() does not type its values, never write a NULL (it breaks the catalogue)
            $text = (string)$text;

            if ($text != strip_tags($text)) {
                $text = $sanitizer->sanitizeFor('body', $text);
                $this->model->addTranslation($language, $text);
            }

            $rows[$language] = [
                'key' => $this->model->getKey(),
                'type' => $this->model->getType(),
                'language' => $language,
                'text' => $text,
                'modificationDate' => $this->model->getModificationDate(),
                'creationDate' => $this->model->getCreationDate(),
                'userOwner' => $this->model->getUserOwner(),
                'userModification' => $this->model->getUserModification(),
            ];
        }

        // all languages or none: a failure in between must not leave the key partially saved
        $write = function () use ($rows, $onlyMissing, $editableLanguages, $user): void {
            $existing = $this->db->fetchAllAssociativeIndexed(
                'SELECT `language`, `text`, `type` FROM ' . $this->db->quoteIdentifier($this->getDatabaseTableName()) . ' WHERE `key` = ?',
                [$this->model->getKey()]
            );

            foreach ($rows as $language => $data) {
                if (isset($existing[$language])) {
                    // an unchanged language keeps its modification info and is not overwritten from a stale object
                    if ($onlyMissing || ((string)$existing[$language]['text'] === $data['text']
                            && ($existing[$language]['type'] ?: 'simple') === $data['type'])) {
                        unset($rows[$language]);
                    }
                }
            }

            // a restricted editor may still save an object that holds the other languages unchanged, but a change to
            // one of them rejects the whole save: nothing is written and POST_SAVE is not dispatched
            if ($editableLanguages !== null) {
                $deniedLanguages = array_diff(array_map('strval', array_keys($rows)), $editableLanguages);
                if ($deniedLanguages) {
                    throw new AccessDeniedException(sprintf(
                        'User %s is not allowed to edit the %s translation of "%s"',
                        $user?->getUsername() ?? 'N/A', // @phpstan-ignore-line
                        implode(', ', $deniedLanguages),
                        $this->model->getKey()
                    ));
                }
            }

            foreach ($rows as $data) {
                // the creation date and owner of an existing language stay as they are
                $this->insertOrUpdate($data, $onlyMissing ? [] : ['type', 'text', 'modificationDate', 'userModification']);
            }
        };

        try {
            $this->db->transactional($write);
        } catch (TableNotFoundException $e) {
            // CREATE TABLE commits implicitly in MySQL, so it runs only for a missing table and never inside a
            // transaction of the caller, whose earlier writes a later rollback could not undo any more
            if ($this->db->isTransactionActive()) {
                throw $e;
            }

            $this->createOrUpdateTable();
            $this->db->transactional($write);
        }
    }

    /**
     * @param string[] $updateColumns the columns written if the row exists, none leaves an existing row untouched
     */
    private function insertOrUpdate(array $data, array $updateColumns): void
    {
        $columns = array_map(fn (string $column) => $this->db->quoteIdentifier($column), array_keys($data));
        $params = array_values($data);

        $updates = [];
        foreach ($updateColumns as $column) {
            $updates[] = $this->db->quoteIdentifier($column) . ' = ?';
            $params[] = $data[$column];
        }

        // without update columns a no-op update on duplicate keys (unlike INSERT IGNORE, other errors are still reported)
        $this->db->executeStatement(
            'INSERT INTO ' . $this->db->quoteIdentifier($this->getDatabaseTableName())
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')'
            . ' ON DUPLICATE KEY UPDATE ' . ($updates ? implode(', ', $updates) : '`key` = `key`'),
            $params
        );
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        try {
            $this->db->delete($this->db->quoteIdentifier($this->getDatabaseTableName()), [$this->db->quoteIdentifier('key') => $this->model->getKey()]);
        } catch (TableNotFoundException) {
            // the domain table is created with its first translation, without it there is nothing to delete
        }
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
     * @throws \Doctrine\DBAL\Exception if the check fails: during a DB outage "no such domain" would make the
     * Translator drop the DB translations silently
     */
    public function isAValidDomain(string $domain): bool
    {
        if (!preg_match(Translation::DOMAIN_PATTERN, $domain)
            || !in_array($domain, Translation::getRegisteredDomains(), true)) {
            return false;
        }

        // an exact comparison: in SHOW TABLES LIKE, the `_` that the domain pattern allows would be a wildcard
        return $this->db->fetchOne(
            'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::TABLE_PREFIX . $domain]
        ) !== false;
    }

    /**
     * Creates the domain table if it does not exist, an existing table is not changed.
     *
     * DDL commits an open transaction implicitly in MySQL, do not call it inside one.
     */
    public function createOrUpdateTable(): void
    {
        // only registered domains with a plain identifier name get a table
        Translation::validateDomain($this->model->getDomain());

        $this->db->executeStatement(self::getCreateTableSql($this->model->getDomain()));
    }

    /**
     * The schema of a domain table, also used by the migration that creates the tables of all registered domains.
     * The domain must match Translation::DOMAIN_PATTERN, it is not quoted.
     *
     * @internal
     */
    public static function getCreateTableSql(string $domain): string
    {
        return 'CREATE TABLE IF NOT EXISTS `' . self::TABLE_PREFIX . $domain . "` (
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
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
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
