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

use DateTime;
use Pimcore\Bundle\CoreBundle\Command\Traits\AssetFilterOptionsTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\CsvOptionTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\DeprecatedOptionNameTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:thumbnails:image',
    description: 'Generate image thumbnails, useful to pre-generate thumbnails in the background',
    aliases: ['thumbnails:image']
)]
class ThumbnailsImageCommand extends AbstractCommand
{
    use AssetFilterOptionsTrait;
    use CsvOptionTrait;
    use DeprecatedOptionNameTrait;
    use Parallelization;

    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Auto formats with a `--skip-<format>` shortcut for `--skip-formats`: the default formats of
     * `pimcore.assets.image.thumbnails.auto_formats`. Listed here so that configure() does not read the config.
     */
    private const array SKIP_FORMAT_SHORTCUTS = ['avif', 'webp'];

    #[\Override]
    protected function configure(): void
    {
        parent::configure();
        self::configureCommand($this);

        $this
            // no -p shortcut for --parent: it is taken by --processes
            ->addAssetFilterOptions('images', null)
            ->addOption(
                'last-modified-since',
                null,
                InputOption::VALUE_REQUIRED,
                'only create thumbnails of images that have been modified since the given date (format: ' . self::DATE_FORMAT . ' )'
            )
            ->addOption(
                'thumbnails',
                't',
                InputOption::VALUE_REQUIRED,
                'only create specified thumbnails (comma separated eg.: thumb1,thumb2)'
            )->addOption(
                'system',
                's',
                InputOption::VALUE_NONE,
                'also create the system thumbnail (used for the tree preview, ...)'
            )->addOption(
                'system-only',
                null,
                InputOption::VALUE_NONE,
                'only create the system thumbnail (used for the tree preview, ...), no other thumbnails'
            )->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'recreate thumbnails, regardless if they exist already'
            )->addOption(
                'skip-medias',
                null,
                InputOption::VALUE_NONE,
                'if config has media queries defined, do not generate thumbnails for them'
            )->addOption(
                'skip-high-res',
                null,
                InputOption::VALUE_NONE,
                'do not generate high-res (@2x) versions of thumbnails'
            )->addOption(
                'skip-formats',
                null,
                InputOption::VALUE_REQUIRED,
                'if target image format is set to auto in config, do not generate images in these auto formats (comma separated eg.: avif,webp)',
                null,
                // a closure, so the config is only read for shell completion
                static fn (): array => self::getEnabledAutoFormats()
            );

        foreach (self::SKIP_FORMAT_SHORTCUTS as $autoFormat) {
            $this->addOption(
                'skip-' . $autoFormat,
                null,
                InputOption::VALUE_NONE,
                sprintf('same as <comment>--skip-formats=%s</comment>', $autoFormat)
            );
        }

        $this->addDeprecatedOptionName('pathPattern', 'path-pattern');
    }

    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        $list = new Asset\Listing();

        // Recently added or changed items are more likely to need thumbnails, start with those in case process is cut short
        $list->setOrderKey('modificationDate');
        $list->setOrder('DESC');

        $conditionVariables = [];

        // get only images
        $conditions = ["type = 'image'"];

        $this->addAssetFilterConditions($input, $conditions, $conditionVariables);

        if ($lastModifiedSince = $input->getOption('last-modified-since')) {
            $lastModifiedSinceDate = DateTime::createFromFormat(self::DATE_FORMAT, $lastModifiedSince);
            // getLastErrors() also catches overflowing values like "2024-13-45 25:00:00", which createFromFormat() accepts
            if (!$lastModifiedSinceDate || DateTime::getLastErrors()) {
                throw new InvalidArgumentException('--last-modified-since must be a date in the format ' . self::DATE_FORMAT . ', "' . $lastModifiedSince . '" given.');
            }
            $conditions[] = 'modificationDate >= ?';
            $conditionVariables[] = $lastModifiedSinceDate->getTimestamp();
        }

        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);

        $thumbnailNames = $this->fetchThumbnailNames($input);

        $unknownFormats = array_diff($this->getSkippedAutoFormats($input), self::getEnabledAutoFormats());
        if ($unknownFormats) {
            $this->io->note('Unknown or disabled auto format(s) in --skip-formats: ' . implode(', ', $unknownFormats));
        }

        $items = [];
        foreach ($list->loadIdList() as $assetId) {
            foreach ($thumbnailNames as $thumbnailName) {
                $items[] = self::encodeItem($assetId, $thumbnailName);
            }
        }

        return $items;
    }

    /**
     * @return string[] names of the thumbnail configs to generate for each image
     *
     * @throws InvalidArgumentException if --system-only is combined with --system or --thumbnails
     */
    private function fetchThumbnailNames(InputInterface $input): array
    {
        $allowedThumbs = $this->getCsvOptionValues($input, 'thumbnails');
        $systemThumbnailName = Image\Thumbnail\Config::getPreviewConfig()->getName();

        if ($input->getOption('system-only')) {
            if ($input->getOption('system') || $allowedThumbs) {
                throw new InvalidArgumentException('--system-only cannot be combined with --system or --thumbnails.');
            }

            return [$systemThumbnailName];
        }

        $thumbnailNames = array_map(
            static fn (Image\Thumbnail\Config $config): string => $config->getName(),
            (new Image\Thumbnail\Config\Listing())->getThumbnails()
        );

        if ($allowedThumbs) {
            $unknownThumbs = array_diff($allowedThumbs, $thumbnailNames);
            if ($unknownThumbs) {
                $this->io->note('Unknown thumbnail config(s), skipping: ' . implode(', ', $unknownThumbs));
            }
            $thumbnailNames = array_intersect($thumbnailNames, $allowedThumbs);
        }

        if ($input->getOption('system')) {
            // the system thumbnail can be a regular thumbnail config (pimcore.assets.preview_image_thumbnail)
            $thumbnailNames[] = $systemThumbnailName;
        }

        return array_values(array_unique($thumbnailNames));
    }

    private static function encodeItem(int $assetId, string $thumbnailName): string
    {
        return json_encode([$assetId, $thumbnailName], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{int, string} the asset ID and the thumbnail config name
     *
     * @throws InvalidArgumentException if the item was not created by encodeItem()
     */
    private static function decodeItem(string $item): array
    {
        try {
            $decoded = json_decode($item, true, 2, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $decoded = null;
        }

        if (!is_array($decoded) || !array_is_list($decoded) || count($decoded) !== 2
            || !is_int($decoded[0]) || !is_string($decoded[1])) {
            throw new InvalidArgumentException(sprintf('Invalid item "%s", expected [<asset ID>, "<thumbnail name>"].', $item));
        }

        return $decoded;
    }

    protected function runSingleCommand(string $item, InputInterface $input, OutputInterface $output): void
    {
        [$assetId, $thumbnailConfigName] = self::decodeItem($item);

        $image = Image::getById($assetId);
        if (!$image) {
            $this->io->error('No image with ID=' . $assetId . ' found. Has the image been deleted or is the asset of another type?');

            return;
        }

        $thumbnailsToGenerate = $this->fetchThumbnailConfigs($input, $thumbnailConfigName);
        if (!$thumbnailsToGenerate) {
            $this->io->error('Thumbnail config "' . $thumbnailConfigName . '" not found. Has it been deleted or renamed?');

            return;
        }

        if ($input->getOption('force')) {
            $image->clearThumbnail($thumbnailConfigName);
        }

        foreach ($thumbnailsToGenerate as $thumbnailConfig) {
            $thumbnail = $image->getThumbnail($thumbnailConfig);
            $path = $thumbnail->getPath(['deferredAllowed' => false]);

            // triggers fetching the thumbnail info and updating the asset cache table if width or height are not in the cache
            $thumbnail->getDimensions();

            if ($output->isVerbose()) {
                $output->writeln(
                    sprintf(
                        'generated thumbnail for image [%d] | file: %s',
                        $image->getId(),
                        $path
                    )
                );
            }
        }
    }

    /**
     * @return Asset\Image\Thumbnail\Config[] empty if the config does not exist (anymore)
     */
    private function fetchThumbnailConfigs(InputInterface $input, string $thumbnailConfigName): array
    {
        $thumbnailConfig = Image\Thumbnail\Config::getByName($thumbnailConfigName);
        if (!$thumbnailConfig) {
            return [];
        }

        $resolutions = $input->getOption('skip-high-res') ? [1.0] : [1.0, 2.0];
        $skippedFormats = $this->getSkippedAutoFormats($input);

        $thumbnailsToGenerate = [];

        // the config as it is, unless its factor is one the loop below generates anyway (no factor = 1)
        if (!in_array((float) ($thumbnailConfig->getHighResolution() ?: 1), $resolutions, true)) {
            $thumbnailsToGenerate[] = $thumbnailConfig;
        }

        $mediaConfigs = [$thumbnailConfig];
        if (!$input->getOption('skip-medias')) {
            foreach (array_keys($thumbnailConfig->getMedias()) as $mediaName) {
                $mediaName = (string) $mediaName;
                if ($mediaName !== 'default') {
                    $mediaConfig = clone $thumbnailConfig;
                    $mediaConfig->selectMedia($mediaName);
                    $mediaConfigs[] = $mediaConfig;
                }
            }
        }

        foreach ($mediaConfigs as $mediaConfig) {
            foreach ($resolutions as $resolution) {
                $resConfig = clone $mediaConfig;
                $resConfig->setHighResolution($resolution);
                $thumbnailsToGenerate[] = $resConfig;

                if ($resConfig->getFormat() === 'SOURCE') {
                    foreach ($resConfig->getAutoFormatThumbnailConfigs() as $autoFormat => $autoFormatThumbnailConfig) {
                        if (!in_array($autoFormat, $skippedFormats, true)) {
                            $thumbnailsToGenerate[] = $autoFormatThumbnailConfig;
                        }
                    }
                }
            }
        }

        return $thumbnailsToGenerate;
    }

    /**
     * @return string[] the auto formats from --skip-formats and the --skip-<format> shortcuts
     */
    private function getSkippedAutoFormats(InputInterface $input): array
    {
        $formats = $this->getCsvOptionValues($input, 'skip-formats');
        foreach (self::SKIP_FORMAT_SHORTCUTS as $autoFormat) {
            if ($input->getOption('skip-' . $autoFormat)) {
                $formats[] = $autoFormat;
            }
        }

        return array_values(array_unique($formats));
    }

    /**
     * @return string[]
     */
    private static function getEnabledAutoFormats(): array
    {
        return array_keys(array_filter(
            Image\Thumbnail\Config::getAutoFormats(),
            static fn (array $autoFormatConfig): bool => (bool) $autoFormatConfig['enabled']
        ));
    }

    protected function getItemName(?int $count): string
    {
        return $count === 1 ? 'thumbnail' : 'thumbnails';
    }
}
