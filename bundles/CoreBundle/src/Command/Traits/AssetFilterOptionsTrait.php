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

use Pimcore\Db\Helper;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * The `--id`, `--parent` and `--path-pattern` options shared by the commands that process a selection of assets.
 *
 * @internal
 *
 * @phpstan-require-extends Command
 */
trait AssetFilterOptionsTrait
{
    use IdOptionTrait;

    /**
     * @param string $subject what the command processes, e.g. "assets" or "images", used in the option descriptions
     * @param string|null $parentShortcut shortcut of --parent; null where it clashes, e.g. with --processes (-p)
     *
     * @return $this
     */
    protected function addAssetFilterOptions(string $subject = 'assets', ?string $parentShortcut = 'p'): static
    {
        return $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('only process %s with these IDs (comma-separated, repeatable)', $subject)
            )
            ->addOption(
                'parent',
                $parentShortcut,
                InputOption::VALUE_REQUIRED,
                sprintf('only process %s in these folders (comma-separated folder IDs, e.g. 543,1077)', $subject)
            )
            ->addOption(
                'path-pattern',
                null,
                InputOption::VALUE_REQUIRED,
                sprintf('only process %s whose path + filename match this regex, example: ^/Sample.*urban.jpg$', $subject)
            );
    }

    /**
     * Appends the SQL conditions for the options added by addAssetFilterOptions().
     *
     * @param string[] $conditions SQL conditions, combined with AND by the caller
     * @param array<int, mixed> $conditionVariables positional parameters of $conditions
     *
     * @throws InvalidArgumentException if --parent is not a list of asset folder IDs or --id is invalid
     */
    protected function addAssetFilterConditions(InputInterface $input, array &$conditions, array &$conditionVariables): void
    {
        if ($ids = $this->getIdOptionValues($input)) {
            $conditions[] = sprintf('id IN (%s)', implode(',', $ids));
        }

        if (null !== $parentOption = $input->getOption('parent')) {
            $parentConditions = [];
            foreach (explode(',', (string) $parentOption) as $parentId) {
                $parentId = trim($parentId);
                $parent = ctype_digit($parentId) ? Asset::getById((int) $parentId) : null;
                if (!$parent instanceof Asset\Folder) {
                    // no exit(): it would skip the console TERMINATE event (e.g. --maintenance-mode would stay active)
                    throw new InvalidArgumentException(sprintf('"%s" is not a valid asset folder ID.', $parentId), Command::INVALID);
                }

                $parentConditions[] = 'path LIKE ?';
                $conditionVariables[] = Helper::escapeLike($parent->getRealFullPath()) . '/%';
            }
            $conditions[] = '(' . implode(' OR ', $parentConditions) . ')';
        }

        if (null !== $regex = $input->getOption('path-pattern')) {
            $conditions[] = 'CONCAT(`path`, filename) REGEXP ?';
            $conditionVariables[] = $regex;
        }
    }
}
