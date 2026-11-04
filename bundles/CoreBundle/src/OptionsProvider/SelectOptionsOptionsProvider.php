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

namespace Pimcore\Bundle\CoreBundle\OptionsProvider;

use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;
use Pimcore\Model\Exception\NotFoundException;

/**
 * Options of select and multiselect fields from a select options configuration (options provider data = its ID).
 *
 * The class name is stored in class definitions and checked by Config::isConfiguredAsOptionsProvider(), so it must not be
 * renamed or moved.
 *
 * @api
 */
class SelectOptionsOptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * @throws NotFoundException when the select options configuration is not set or does not exist
     */
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        if (!$fieldDefinition instanceof Data\OptionsProviderInterface) {
            // only select and multiselect fields can use an options provider, so this is a misconfiguration
            Logger::warning(sprintf(
                'The select options provider was called for the field "%s" of type "%s", which has no options provider data.',
                $fieldDefinition->getName(),
                $fieldDefinition->getFieldType()
            ));

            return [];
        }

        $configurationId = trim((string) $fieldDefinition->getOptionsProviderData());
        $selectOptionsConfiguration = $configurationId !== '' ? Config::getById($configurationId) : null;
        if ($selectOptionsConfiguration === null) {
            $class = $context['class'] ?? null;
            $fieldName = ($class instanceof ClassDefinition ? $class->getName() . '.' : '') . $fieldDefinition->getName();

            throw new NotFoundException(
                sprintf('Missing select options configuration "%s" for field "%s"', $configurationId, $fieldName)
            );
        }

        return array_map(
            static fn (SelectOption $selectOption) => [
                'value' => $selectOption->getValue(),
                'key' => $selectOption->getLabel(),
            ],
            $selectOptionsConfiguration->getSelectOptions(),
        );
    }

    /**
     * The options come from a versioned configuration, not from the object data, so they are the same for every object
     * (no per-object handling of dynamic options in SelectionProviderTrait).
     */
    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): string|array|null
    {
        if ($fieldDefinition instanceof Data\Select || $fieldDefinition instanceof Data\Multiselect) {
            return $fieldDefinition->getDefaultValue();
        }

        return null;
    }
}
