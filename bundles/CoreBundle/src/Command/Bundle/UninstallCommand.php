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

namespace Pimcore\Bundle\CoreBundle\Command\Bundle;

use Pimcore\Bundle\CoreBundle\Command\Bundle\Helper\PostStateChange;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class UninstallCommand extends AbstractBundleCommand
{
    public function __construct(PimcoreBundleManager $bundleManager, private readonly PostStateChange $postStateChangeHelper)
    {
        parent::__construct($bundleManager);
    }

    protected function configure(): void
    {
        $this
            ->setName($this->buildName('uninstall'))
            ->configureDescriptionAndHelp('Uninstalls a bundle')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle to uninstall')
            ->configureFailWithoutErrorOption();

        PostStateChange::configureStateChangeCommandOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundle = $this->getBundle();

        $installer = $this->bundleManager->getInstaller($bundle);

        if (null !== $installer && !$installer->isInstalled()) {
            $this->io->success(sprintf('Bundle "%s" is not installed', $bundle->getName()));

            return Command::SUCCESS;
        }

        if (!$this->bundleManager->canBeUninstalled($bundle)) {
            return $this->handlePrerequisiteError(sprintf('Bundle "%s" can not be uninstalled', $bundle->getName()));
        }

        // errors from the installer itself are not a prerequisite problem, let them fail the command
        try {
            $this->bundleManager->uninstall($bundle);
        } finally {
            $this->writeInstallerOutput($installer);
        }

        $this->io->success(sprintf('Bundle "%s" was successfully uninstalled', $bundle->getName()));

        $this->postStateChangeHelper->runPostStateChangeCommands(
            $this->io,
            $this->getApplication()->getKernel()->getEnvironment()
        );

        return Command::SUCCESS;
    }
}
