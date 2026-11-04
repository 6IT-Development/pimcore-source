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

namespace Pimcore\Bundle\CoreBundle\Command\Asset;

use Pimcore\Bundle\CoreBundle\Command\Traits\AssetBatchTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\AssetFilterOptionsTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db\Helper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:remove-custom-setting',
    description: 'Removes a custom setting from assets',
)]
class RemoveCustomSettingCommand extends AbstractCommand
{
    use AssetBatchTrait;
    use AssetFilterOptionsTrait;

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the custom setting to remove')
            ->addAssetFilterOptions();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // customSettings holds a serialized array: match the serialized key, so `_`/`%` in the name aren't wildcards
        // and a name that is only a prefix/suffix of another key doesn't match (it can still match a value)
        $key = array_key_first([$name => true]);
        $serializedKey = is_int($key) ? 'i:' . $key . ';' : 's:' . strlen($key) . ':"' . $key . '";';
        $conditions = ['customSettings LIKE ?'];
        $conditionVariables = ['%' . Helper::escapeLike(str_replace('\\', '\\\\', $serializedKey)) . '%'];

        $this->addAssetFilterConditions($input, $conditions, $conditionVariables);

        // keyset pagination: processed assets no longer match the customSettings condition, so an OFFSET would skip
        // assets of the shrinking result set
        $count = 0;
        foreach ($this->iterateAssets($conditions, $conditionVariables) as $asset) {
            $output->writeln($asset->getRealFullPath());
            $asset->removeCustomSetting($name);
            $asset->getDao()->updateCustomSettings();
            $count++;
        }

        $output->writeln(sprintf('Removed custom setting "%s" from %d asset(s).', $name, $count));

        return self::SUCCESS;
    }
}
