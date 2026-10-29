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

namespace Pimcore\Bundle\SeoBundle\Sitemap\Element\Processor;

use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\ProcessorInterface;
use Pimcore\Logger;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

/**
 * Adds change frequency and priority entries based on document properties.
 */
class PropertiesProcessor implements ProcessorInterface
{
    public const string PROPERTY_CHANGE_FREQUENCY = 'sitemaps_changefreq';

    public const string PROPERTY_PRIORITY = 'sitemaps_priority';

    private const array CHANGE_FREQUENCIES = [
        UrlConcrete::CHANGEFREQ_ALWAYS,
        UrlConcrete::CHANGEFREQ_HOURLY,
        UrlConcrete::CHANGEFREQ_DAILY,
        UrlConcrete::CHANGEFREQ_WEEKLY,
        UrlConcrete::CHANGEFREQ_MONTHLY,
        UrlConcrete::CHANGEFREQ_YEARLY,
        UrlConcrete::CHANGEFREQ_NEVER,
    ];

    public function process(Url $url, ElementInterface $element, GeneratorContextInterface $context): Url|UrlConcrete|null
    {
        if (!$url instanceof UrlConcrete) {
            return $url;
        }

        // the properties are free text maintained by editors - an invalid value must only skip the attribute,
        // as Presta throws on it and would abort the whole sitemap
        $changeFreq = $element->getProperty(self::PROPERTY_CHANGE_FREQUENCY);
        if (null !== $changeFreq && '' !== $changeFreq) {
            $normalized = is_string($changeFreq) ? strtolower(trim($changeFreq)) : null;
            if (in_array($normalized, self::CHANGE_FREQUENCIES, true)) {
                $url->setChangefreq($normalized);
            } else {
                $this->logInvalidValue($element, self::PROPERTY_CHANGE_FREQUENCY, $changeFreq);
            }
        }

        $priority = $element->getProperty(self::PROPERTY_PRIORITY);
        if (null !== $priority && '' !== $priority) {
            $normalized = is_string($priority) ? str_replace(',', '.', trim($priority)) : $priority;
            if (is_numeric($normalized) && $normalized >= 0 && $normalized <= 1) {
                $url->setPriority((float) $normalized);
            } else {
                $this->logInvalidValue($element, self::PROPERTY_PRIORITY, $priority);
            }
        }

        return $url;
    }

    private function logInvalidValue(ElementInterface $element, string $property, mixed $value): void
    {
        Logger::warning(sprintf(
            'Sitemap: ignoring invalid value %s of property "%s" on %s %s (%s)',
            is_scalar($value) ? var_export($value, true) : get_debug_type($value),
            $property,
            Service::getElementType($element),
            $element->getId(),
            $element->getRealFullPath()
        ));
    }
}
