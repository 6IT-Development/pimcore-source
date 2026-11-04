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

namespace Pimcore\Tests\Model\Command;

use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveCustomSettingCommandTest extends ModelTestCase
{
    /**
     * C-02: with OFFSET pagination, every processed batch shrank the result set and the next page skipped assets.
     * More assets than one batch (100) must all lose the setting.
     */
    public function testRemovesTheSettingFromAllAssets(): void
    {
        $ids = [];
        for ($i = 0; $i < 120; $i++) {
            $folder = TestHelper::createAssetFolder('c02-', false);
            $folder->setCustomSetting('c02_setting', 'value');
            $folder->setCustomSetting('keep', 'value');
            $folder->save();
            $ids[] = $folder->getId();
        }

        // `_` must not act as LIKE wildcard: this setting must stay
        $other = TestHelper::createAssetFolder('c02-', false);
        $other->setCustomSetting('c02xsetting', 'value');
        $other->save();

        $application = new Application(Pimcore::getKernel());
        $tester = new CommandTester($application->find('pimcore:assets:remove-custom-setting'));

        $this->assertSame(Command::SUCCESS, $tester->execute(['name' => 'c02_setting']));
        $this->assertStringContainsString('from 120 asset(s)', $tester->getDisplay());

        foreach ($ids as $id) {
            $asset = Asset::getById($id, ['force' => true]);
            $this->assertNull($asset->getCustomSetting('c02_setting'), 'asset ' . $id);
            $this->assertSame('value', $asset->getCustomSetting('keep'), 'asset ' . $id);
        }

        $this->assertSame('value', Asset::getById($other->getId(), ['force' => true])->getCustomSetting('c02xsetting'));
    }
}
