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

use LibXMLError;
use Pimcore\Console\AbstractCommand;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'internal:unicode-cldr-language-territory-generator',
    description: 'For internal use only',
    hidden: true
)]
class InternalUnicodeCldrLanguageTerritoryGeneratorCommand extends AbstractCommand
{
    /**
     * CLDR release tag the data is taken from, pinned so the generated mapping is reproducible
     */
    private const string CLDR_RELEASE = 'release-49';

    private const int DOWNLOAD_TIMEOUT = 30;

    public function __construct(private readonly LocaleServiceInterface $localeService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = 'https://raw.githubusercontent.com/unicode-org/cldr/' . self::CLDR_RELEASE . '/common/supplemental/supplementalData.xml';
        $context = stream_context_create(['http' => ['timeout' => self::DOWNLOAD_TIMEOUT]]);
        $data = @file_get_contents($source, false, $context);
        if ($data === false) {
            throw new RuntimeException('Could not download ' . $source . ': ' . (error_get_last()['message'] ?? 'unknown error'));
        }

        $previousUseErrors = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($data, null, LIBXML_NOCDATA);
            if ($xml === false) {
                $errors = array_map(static fn (LibXMLError $error): string => trim($error->message), libxml_get_errors());

                throw new RuntimeException('Could not parse ' . $source . ': ' . implode('; ', $errors));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }

        if (!isset($xml->territoryInfo->territory)) {
            throw new RuntimeException('No territoryInfo found in ' . $source);
        }

        $languageRawData = [];

        foreach ($xml->territoryInfo->territory as $territory) {
            foreach ($territory->languagePopulation as $language) {
                $languageCode = (string)$language['type'];
                if ($this->localeService->isLocale($languageCode)) {
                    $populationAbsolute = $territory['population'] * $language['populationPercent'] / 100;

                    if (!isset($languageRawData[$languageCode])) {
                        $languageRawData[$languageCode] = [];
                    }

                    if ($this->localeService->isLocale($languageCode . '_' . $territory['type'])) {
                        $languageRawData[$languageCode][] = [
                            'country' => (string)$territory['type'],
                            'population' => $populationAbsolute,
                        ];
                    }
                }
            }
        }

        $finalData = [];

        foreach ($languageRawData as $languageCode => $rawLanguage) {
            usort($rawLanguage, function ($a, $b) {
                if ($a['population'] == $b['population']) {
                    return 0;
                }

                return ($a['population'] > $b['population']) ? -1 : 1;
            });

            $finalData[$languageCode] = [];
            foreach ($rawLanguage as $territory) {
                $finalData[$languageCode][] = $territory['country'];
            }
        }

        $contents = to_php_data_file_format($finalData);
        $dataFile = PIMCORE_PATH . '/bundles/CoreBundle/public/misc/cldr-language-territory-mapping.php';
        File::putPhpFile($dataFile, $contents);

        $this->output->writeln('Updated mappings in ' . $dataFile);

        return 0;
    }
}
