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

namespace Pimcore\Bundle\CoreBundle\Command;

use InvalidArgumentException;
use Pimcore\Bundle\CoreBundle\Command\Traits\DeprecatedOptionNameTrait;
use Pimcore\Cache\Tool\Warming;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:cache:warming',
    description: 'Warm up caches'
)]
class CacheWarmingCommand extends AbstractCommand
{
    use DeprecatedOptionNameTrait;

    private const array VALID_TYPES = [
        'document',
        'asset',
        'object',
    ];

    private const array VALID_DOCUMENT_TYPES = [
        'page',
        'snippet',
        'folder',
        'link',
    ];

    private const array VALID_ASSET_TYPES = [
        'archive',
        'audio',
        'document',
        'folder',
        'image',
        'text',
        'unknown',
        'video',
    ];

    private const array VALID_OBJECT_TYPES = [
        'object',
        'folder',
        'variant',
    ];

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'types',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('Perform warming only for this types of elements. Valid options: %s', $this->humanList(self::VALID_TYPES)),
                null,
                self::VALID_TYPES
            )
            ->addOption(
                'document-types',
                'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of documents. Valid options: %s', $this->humanList(self::VALID_DOCUMENT_TYPES)),
                null,
                self::VALID_DOCUMENT_TYPES
            )
            ->addOption(
                'asset-types',
                'a',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of assets. Valid options: %s', $this->humanList(self::VALID_ASSET_TYPES)),
                null,
                self::VALID_ASSET_TYPES
            )
            ->addOption(
                'object-types',
                'o',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of objects. Valid options: %s', $this->humanList(self::VALID_OBJECT_TYPES)),
                null,
                self::VALID_OBJECT_TYPES
            )
            ->addOption(
                'classes',
                'c',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Restrict object warming to these classes (only valid for objects!). Valid options: class names of your classes defined in Pimcore'
            );

        $this
            ->addDeprecatedOptionName('documentTypes', 'document-types')
            ->addDeprecatedOptionName('assetTypes', 'asset-types')
            ->addDeprecatedOptionName('objectTypes', 'object-types');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // --maintenance-mode is a global option, defined by Pimcore\Console\Application
        if ($input->getOption('maintenance-mode')) {
            // set the timeout between each iteration to 0 if maintenance mode is on, because
            // we don't have to care about the load on the server
            Warming::setTimoutBetweenIteration(0);
        }

        try {
            $types = $this->getArrayOption('types', self::VALID_TYPES, 'type') ?: self::VALID_TYPES;
            $documentTypes = $this->getArrayOption('document-types', self::VALID_DOCUMENT_TYPES, 'document type');
            $assetTypes = $this->getArrayOption('asset-types', self::VALID_ASSET_TYPES, 'asset type');
            $objectTypes = $this->getArrayOption('object-types', self::VALID_OBJECT_TYPES, 'object type');
            $objectClasses = $this->getObjectClasses();
        } catch (InvalidArgumentException $e) {
            $this->io->error($e->getMessage());

            return self::INVALID;
        }

        if (in_array('document', $types)) {
            $this->writeWarmingMessage('document', $documentTypes);
            Warming::documents($documentTypes);
        }

        if (in_array('asset', $types)) {
            $this->writeWarmingMessage('asset', $assetTypes);
            Warming::assets($assetTypes);
        }

        if (in_array('object', $types)) {
            $extraInfo = count($objectClasses) ? ' from class: ' . implode(',', $objectClasses) : '';
            $this->writeWarmingMessage('object', $objectTypes, $extraInfo);
            Warming::objects($objectTypes, $objectClasses);
        }

        return self::SUCCESS;
    }

    protected function writeWarmingMessage(string $type, array $types, string $extra = ''): void
    {
        $output = sprintf('Warming <comment>%s</comment> cache', $type);
        if ($types) {
            $output .= sprintf(' for types %s', $this->humanList($types, 'and', '<info>%s</info>'));
        } else {
            $output .= ' for <info>all</info> types';
        }

        if (!empty($extra)) {
            $output .= $extra;
        }

        $output .= '...';
        $this->output->writeln($output);
    }

    /**
     * A,B,C -> A, B or C (with an optional template for each item)
     *
     *
     */
    protected function humanList(array $list, string $glue = 'or', ?string $template = null): string
    {
        if (null !== $template) {
            array_walk($list, function (&$item) use ($template) {
                $item = sprintf($template, $item);
            });
        }

        if (count($list) > 1) {
            $lastElement = array_pop($list);

            return implode(', ', $list) . ' ' . $glue . ' ' . $lastElement;
        } else {
            return implode(', ', $list);
        }
    }

    /**
     * Returns the values of an array option after checking them against $validValues
     *
     * @param string[] $validValues
     *
     * @return string[] empty if the option was not passed (= all types)
     *
     * @throws InvalidArgumentException if a value is not in $validValues
     */
    protected function getArrayOption(string $option, array $validValues, string $singular): array
    {
        $values = $this->input->getOption($option);

        foreach ($values as $value) {
            if (!in_array($value, $validValues, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid %s: %s. Valid options: %s',
                    $singular,
                    $value,
                    $this->humanList($validValues)
                ));
            }
        }

        return $values;
    }

    /**
     * Returns the --classes values after checking that each one is the name of a class definition
     *
     * @return string[]
     *
     * @throws InvalidArgumentException if a class does not exist
     */
    private function getObjectClasses(): array
    {
        $classes = $this->input->getOption('classes');

        $unknownClasses = array_filter($classes, static fn (string $class): bool => !ClassDefinition::getByName($class));
        if ($unknownClasses) {
            throw new InvalidArgumentException(sprintf('Unknown class(es): %s', implode(', ', $unknownClasses)));
        }

        return $classes;
    }
}
