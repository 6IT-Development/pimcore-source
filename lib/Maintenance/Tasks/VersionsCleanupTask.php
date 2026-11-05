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

namespace Pimcore\Maintenance\Tasks;

use Carbon\Carbon;
use Pimcore;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Version;
use Pimcore\SystemSettingsConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @internal
 */
readonly class VersionsCleanupTask implements TaskInterface
{
    private const int BATCH_SIZE = 1000;

    public function __construct(private LoggerInterface $logger, private SystemSettingsConfig $config)
    {
    }

    public function execute(): void
    {
        $this->doVersionCleanup();
        $this->doAutoSaveVersionCleanup();
    }

    private function doAutoSaveVersionCleanup(): void
    {
        $date = Carbon::now();
        $date->subHours(72);

        $list = new Version\Listing();
        $ids = $list->setLoadAutoSave(true)
            ->setCondition(' `autoSave` = 1 AND `date` < ' . $date->getTimestamp())
            ->loadIdList();

        $this->logger->debug('Auto-save versions to delete: ' . count($ids));
        foreach ($ids as $id) {
            try {
                // deleted in between, e.g. together with its element
                Version::getById((int)$id)?->delete();
            } catch (Throwable $e) {
                $this->logger->error('Unable to delete auto-save version ' . $id . ': ' . $e);
            }
        }
    }

    private function doVersionCleanup(): void
    {
        $systemSettingsConfig = $this->config->getSystemSettingsConfig();
        $conf = [
            'document' => $systemSettingsConfig['documents']['versions'] ?? null,
            'asset' => $systemSettingsConfig['assets']['versions'] ?? null,
            'object' => $systemSettingsConfig['objects']['versions'] ?? null,
        ];

        // Not very pretty and should be solved using a repository....
        $dao = (new Version())->getDao();

        foreach ($conf as $elementType => $tConf) {
            //skip cleanup if element is null
            if (is_null($tConf)) {
                continue;
            }
            //skip cleanup if both, 'steps' & 'days', is null
            if (is_null($tConf['steps']) && is_null($tConf['days'])) {
                continue;
            }

            $results = ['deleted' => 0, 'kept' => 0, 'failed' => 0];

            // the batches continue after the last id, so versions that are kept or cannot be deleted are not
            // returned again
            if (isset($tConf['days'])) {
                $deadline = time() - ((int)$tConf['days'] * 86400);
                $lastId = 0;
                while ($ids = $dao->maintenanceGetVersionIdsOlderThan($elementType, $deadline, $lastId, self::BATCH_SIZE)) {
                    foreach ($ids as $id) {
                        $results[$this->cleanupVersion($id, $dao)]++;
                    }
                    $lastId = end($ids);
                    $this->collectGarbage();
                }
            } else {
                // keeps steps + 1 versions per element, as it always did
                $keep = (int)($tConf['steps'] ?? 10) + 1;
                $lastCid = 0;
                while ($cids = $dao->maintenanceGetElementIdsWithMoreVersionsThan($elementType, $keep, $lastCid, self::BATCH_SIZE)) {
                    foreach ($cids as $cid) {
                        foreach ($dao->maintenanceGetVersionIdsBeyond($elementType, $cid, $keep) as $id) {
                            $results[$this->cleanupVersion($id, $dao)]++;
                        }
                        $this->collectGarbage();
                    }
                    $lastCid = end($cids);
                }
            }

            $this->logger->info(sprintf('Versions cleanup (%s): %d deleted, %d kept, %d failed', $elementType, $results['deleted'], $results['kept'], $results['failed']));
        }
    }

    /**
     * Failures are logged and skipped: one version that cannot be deleted must not stop the cleanup
     *
     * @return 'deleted'|'kept'|'failed'
     */
    private function cleanupVersion(int $id, Version\Dao $dao): string
    {
        try {
            $version = Version::getById($id);

            // deleted in between, public, or referenced in the scheduler
            if (!$version || $version->isPublic() || $dao->isVersionUsedInScheduler($version)) {
                return 'kept';
            }

            $element = match ($version->getCtype()) {
                'document' => Document::getById($version->getCid()),
                'asset' => Asset::getById($version->getCid()),
                'object' => DataObject::getById($version->getCid()),
                default => null,
            };

            // keep a version that is newer than the element; versions of deleted elements are removed
            if ($element instanceof Element\ElementInterface && $element->getModificationDate() < $version->getDate()) {
                return 'kept';
            }

            $version->delete();

            return 'deleted';
        } catch (Throwable $e) {
            $this->logger->error('Unable to delete version ' . $id . ': ' . $e);

            return 'failed';
        }
    }

    private function collectGarbage(): void
    {
        // call the garbage collector if memory consumption is > 100MB
        if (memory_get_usage() > 100000000) {
            Pimcore::collectGarbage();
        }
    }
}
