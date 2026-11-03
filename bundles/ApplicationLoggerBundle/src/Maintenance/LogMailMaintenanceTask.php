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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Maintenance;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Config;
use Pimcore\Mail;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Tool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;

readonly class LogMailMaintenanceTask implements TaskInterface
{
    private const int MAIL_BATCH_SIZE = 100;

    public function __construct(
        private Connection      $db,
        private Config          $config,
        private LoggerInterface $logger
    )
    {
    }

    public function execute(): void
    {
        // rows written from now on are left for the next run, they are not part of the mails of this one
        $maxId = (int)$this->db->fetchOne('SELECT MAX(id) FROM ' . ApplicationLoggerDb::TABLE_NAME);
        if (!$maxId) {
            return;
        }

        if (!empty($this->config['applicationlog']['mail_notification']['send_log_summary'])) {
            $receivers = $this->getReceivers();

            if ($receivers) {
                $this->sendSummaryMails($receivers, $maxId);
            }
        }

        // flag them as checked, regardless if email notifications are enabled or not
        // otherwise, when activating email notifications, you'll receive all log-messages from the past and not
        // since the point when you enabled the notifications
        $this->flagAsChecked($maxId);
    }

    /**
     * @param Address[] $receivers
     */
    private function sendSummaryMails(array $receivers, int $maxId): void
    {
        // getting the enums from priority
        $priorityColumnDefinition = $this->db->fetchAllAssociative('SHOW COLUMNS FROM ' . ApplicationLoggerDb::TABLE_NAME . " LIKE 'priority'");

        // type is the actual enum values
        $columnType = reset($priorityColumnDefinition)['Type'];

        // remove unnecessary noise
        $enumValue = explode(',', str_replace(['enum(', ')'], '', $columnType));

        $logLevel = (int)($this->config['applicationlog']['mail_notification']['filter_priority'] ?? null);

        // out of range values would produce an invalid IN() list
        $logLevels = array_slice($enumValue, 0, max(0, $logLevel) + 1);

        $query = 'SELECT * FROM '
            . ApplicationLoggerDb::TABLE_NAME
            . ' WHERE maintenanceChecked IS NULL '
            . 'AND priority IN('
            . implode(',', $logLevels)
            . ') '
            . 'AND id > ? AND id <= ? '
            . 'ORDER BY id ASC '
            . 'LIMIT ' . self::MAIL_BATCH_SIZE;

        $lastId = 0;
        while ($entries = $this->db->fetchAllAssociative($query, [$lastId, $maxId])) {
            // log entries contain user-controlled input, so they must not end up as markup in the mail
            $html = htmlspecialchars(var_export($entries, true), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = "<pre>$html</pre>";
            $mail = new Mail();
            $mail->setIgnoreDebugMode(true);
            $mail->html($html);
            foreach ($receivers as $receiver) {
                $mail->addTo($receiver);
            }
            $mail->subject('Error Log ' . Tool::getHostUrl());
            $mail->send();

            // the next run must not send this batch again if a later one fails
            $lastId = (int)$entries[array_key_last($entries)]['id'];
            $this->flagAsChecked($lastId);
        }
    }

    private function flagAsChecked(int $maxId): void
    {
        $this->db->executeStatement(
            'UPDATE '
            . ApplicationLoggerDb::TABLE_NAME
            . ' SET maintenanceChecked = 1 '
            . 'WHERE id <= ? AND (maintenanceChecked != 1 '
            . 'OR maintenanceChecked IS NULL)',
            [$maxId]
        );
    }

    /**
     * @return Address[]
     */
    private function getReceivers(): array
    {
        $receivers = [];
        $configuredReceivers = (string)($this->config['applicationlog']['mail_notification']['mail_receiver'] ?? '');

        foreach (preg_split('/,|;/', $configuredReceivers, -1, PREG_SPLIT_NO_EMPTY) as $receiver) {
            $receiver = trim($receiver);
            if ('' === $receiver) {
                continue;
            }

            try {
                $receivers[] = new Address($receiver, $receiver);
            } catch (RfcComplianceException $e) {
                $this->logger->warning('Skipping invalid log summary mail receiver: ' . $e->getMessage());
            }
        }

        if (!$receivers) {
            $this->logger->warning('Log summary mails are enabled, but no valid mail receiver is configured');
        }

        return $receivers;
    }
}
