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

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Completion\CompletionInput;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:thumbnails:clear',
    description: 'Clear certain image or video thumbnails (temp. files)'
)]
class ThumbnailsClearCommand extends AbstractCommand
{
    public function __invoke(
        #[Option('video or image', suggestedValues: ['image', 'video'])]
        ?string $type = null,
        #[Option('name of the thumbnail config of which the temp. files should be cleared', suggestedValues: [self::class, 'suggestConfigNames'])]
        ?string $name = null,
    ): int {
        $possibleOptions = ['image', 'video'];
        if (!in_array($type, $possibleOptions, true)) {
            $this->io->error(sprintf('Input option `type` must be one of %s', implode(',', $possibleOptions)));

            return self::INVALID;
        }

        if (!$name) {
            $this->io->error('Input option `name` is required');

            return self::INVALID;
        }

        $configClass = match ($type) {
            'image' => Asset\Image\Thumbnail\Config::class,
            'video' => Asset\Video\Thumbnail\Config::class,
        };
        $thumbConfig = $configClass::getByName($name);
        if (!$thumbConfig) {
            $this->io->error(sprintf('Unable to find %s thumbnail config with name: %s. Nevertheless trying to delete remaining files', $type, $name));
            $thumbConfig = new $configClass();
            $thumbConfig->setName($name);
        }

        $thumbConfig->clearTempFiles();

        return self::SUCCESS;
    }

    /**
     * Completion for --name: the config names of the type given in --type.
     *
     * @internal
     *
     * @return string[]
     */
    public function suggestConfigNames(CompletionInput $input): array
    {
        $configs = match ($input->getOption('type')) {
            'image' => new Asset\Image\Thumbnail\Config\Listing()->getThumbnails(),
            'video' => new Asset\Video\Thumbnail\Config\Listing()->getThumbnails(),
            default => [],
        };

        return array_map(static fn ($config) => $config->getName(), $configs);
    }
}
