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

namespace Pimcore\Model\Notification\Listing;

use Doctrine\DBAL\Exception;
use Pimcore\Logger;
use Pimcore\Model\Listing\Dao\AbstractDao;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Listing;
use UnexpectedValueException;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends AbstractDao
{
    public const string DB_TABLE_NAME = 'notifications';

    public function count(): int
    {
        $sql = sprintf('SELECT COUNT(*) AS num FROM `%s`%s', static::DB_TABLE_NAME, $this->getCondition());

        try {
            $count = (int)$this->db->fetchOne($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (\Exception) {
            $count = 0;
        }

        return $count;
    }

    public function getTotalCount(): int
    {
        return $this->count();
    }

    /**
     *
     * @throws Exception
     */
    public function load(): array
    {
        $notifications = [];
        $sql = sprintf(
            'SELECT id FROM `%s`%s%s%s',
            static::DB_TABLE_NAME,
            $this->getCondition(),
            $this->getOrder(),
            $this->getOffsetLimit()
        );

        $ids = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        foreach ($ids as $id) {
            try {
                $notification = Notification::getById((int)$id);
            } catch (UnexpectedValueException $e) {
                // one invalid row (e.g. of a deleted recipient) must not break the whole list
                Logger::warning(sprintf('Skipping invalid notification %d: %s', $id, $e->getMessage()));

                continue;
            }

            if ($notification instanceof Notification) {
                $notifications[] = $notification;
            }
        }

        $this->model->setNotifications($notifications);

        return $notifications;
    }
}
