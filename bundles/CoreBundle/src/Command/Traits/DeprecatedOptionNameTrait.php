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

namespace Pimcore\Bundle\CoreBundle\Command\Traits;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Keeps a renamed option working under its old name for one release.
 *
 * Values passed under the old name are moved to the new name in initialize(), so the command only reads the new name.
 *
 * @internal
 *
 * @phpstan-require-extends AbstractCommand
 */
trait DeprecatedOptionNameTrait
{
    /**
     * @var array<string, string> new option name by deprecated name
     */
    private array $deprecatedOptionNames = [];

    /**
     * Adds $oldName as deprecated alias of the already defined option $newName, with the same value mode.
     *
     * @return $this
     */
    protected function addDeprecatedOptionName(string $oldName, string $newName): static
    {
        $option = $this->getDefinition()->getOption($newName);

        $mode = $option->isValueRequired() ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
        if ($option->isArray()) {
            $mode |= InputOption::VALUE_IS_ARRAY;
        }

        $this->deprecatedOptionNames[$oldName] = $newName;

        return $this->addOption(
            $oldName,
            null,
            $mode,
            sprintf('Deprecated, use <comment>--%s</comment> instead', $newName)
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        foreach ($this->deprecatedOptionNames as $oldName => $newName) {
            $this->resolveDeprecatedOptionName($input, $oldName, $newName);
        }
    }

    /**
     * Moves the value passed under $oldName to $newName and warns about the old name.
     *
     * Values of array options are merged; a single value option given under both names is rejected.
     *
     * @throws InvalidArgumentException if a single value option is given under both names
     */
    private function resolveDeprecatedOptionName(InputInterface $input, string $oldName, string $newName): void
    {
        $value = $input->getOption($oldName);
        if (null === $value || [] === $value) {
            return;
        }

        trigger_deprecation(
            'pimcore/pimcore',
            '11.5',
            'The "--%s" option of the "%s" command is deprecated, use "--%s" instead.',
            $oldName,
            $this->getName(),
            $newName
        );
        $this->io->warning(sprintf('The --%s option is deprecated, use --%s instead.', $oldName, $newName));

        $newValue = $input->getOption($newName);
        if (is_array($value)) {
            $input->setOption($newName, [...(array) $newValue, ...$value]);
            $input->setOption($oldName, []);
        } elseif (null === $newValue) {
            $input->setOption($newName, $value);
            $input->setOption($oldName, null);
        } else {
            throw new InvalidArgumentException(sprintf('Pass either --%s or --%s, not both.', $newName, $oldName));
        }
    }
}
