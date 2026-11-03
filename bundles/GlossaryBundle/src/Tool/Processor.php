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

namespace Pimcore\Bundle\GlossaryBundle\Tool;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Cache;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tool\DomCrawler;

/**
 * @internal
 */
class Processor
{
    private RequestHelper $requestHelper;

    private EditmodeResolver $editmodeResolver;

    private DocumentResolver $documentResolver;

    private array $blockedTags;

    public function __construct(
        RequestHelper    $requestHelper,
        EditmodeResolver $editmodeResolver,
        DocumentResolver $documentResolver,
        array            $blockedTags = [],
    )
    {
        $this->requestHelper = $requestHelper;
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
        $this->blockedTags = $blockedTags;
    }

    /**
     * Process glossary entries in content string
     *
     *
     */
    public function process(string $content, array $options): string
    {
        if ($this->editmodeResolver->isEditmode()) {
            return $content;
        }

        $locale = $this->requestHelper->getMainRequest()->getLocale();
        $currentDocument = $this->documentResolver->getDocument();
        $uri = $this->requestHelper->getMainRequest()->getRequestUri();

        return $this->parse($content, $options, $locale, $currentDocument, $uri);
    }

    public function parse(string $content, array $options, string $locale, ?Document $document, ?string $uri): string
    {
        $data = $this->getData($locale);
        if (empty($data)) {
            return $content;
        }

        $options = array_merge([
            'limit' => -1,
        ], $options);

        $entries = [];
        foreach ($data as $entry) {
            $linkTarget = $entry['linkTarget'] ?? '';
            $linkTargetTrimmed = rtrim((string)$linkTarget, ' /');
            if ($document instanceof Document) {
                // check if the current document is the target link (id check)
                if ($entry['linkType'] == 'internal' && $document->getId() == $linkTarget) {
                    continue;
                }

                // check if the current document is the target link (path check)
                if ($document->getFullPath() == $linkTargetTrimmed) {
                    continue;
                }
            }

            // check if the current URI is the target link (path check)
            if ($uri === $linkTargetTrimmed) {
                continue;
            }

            $entry['count'] = 0;
            $entries[] = $entry;
        }

        if (!$entries) {
            return $content;
        }

        // only text nodes are searched, so terms are never replaced inside tag names, attribute values or markup
        // inserted for another term, and existing links and abbreviations are never nested, whatever blocked_tags says
        $html = new DomCrawler($content);
        $root = $html->getNode(0);
        $textNodes = (new \DOMXPath($root->ownerDocument))->query('//text()[normalize-space()][not(ancestor::a or ancestor::abbr)]');

        foreach (iterator_to_array($textNodes) as $textNode) {
            if ($this->hasBlockedAncestor($textNode)) {
                continue;
            }

            $this->replaceInTextNode($textNode, $entries, (int)$options['limit']);
        }

        $result = $html->html();
        $html->clear();

        return $result;
    }

    /**
     * A blocked tag protects its whole subtree, e.g. a <span> inside <pre> or a <strong> inside <h2>.
     */
    private function hasBlockedAncestor(\DOMNode $node): bool
    {
        for ($parent = $node->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
            if (in_array(strtolower($parent->nodeName), $this->blockedTags, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replaces the glossary terms of one text node in a single pass: at each position the leftmost match wins, and
     * on a tie the entry that comes first (the longest text). The matched text is kept as written in the content (not
     * replaced with the casing of the entry) and wrapped in elements built with the DOM API, so text and attribute
     * values are escaped by the serializer.
     */
    private function replaceInTextNode(\DOMText $textNode, array &$entries, int $limit): void
    {
        $text = $textNode->data;
        $offset = 0;
        $fragment = null;

        while (true) {
            $match = null;
            foreach ($entries as $index => $entry) {
                if ($limit >= 0 && $entry['count'] >= $limit) {
                    continue;
                }

                if (preg_match($entry['search'], $text, $m, PREG_OFFSET_CAPTURE, $offset) === 1
                    && ($match === null || $m[0][1] < $match['offset'])
                ) {
                    $match = ['index' => $index, 'offset' => $m[0][1], 'text' => $m[0][0]];
                }
            }

            if ($match === null || $match['text'] === '') {
                break;
            }

            $doc = $textNode->ownerDocument;
            $fragment ??= $doc->createDocumentFragment();
            if ($match['offset'] > $offset) {
                $fragment->appendChild($doc->createTextNode(substr($text, $offset, $match['offset'] - $offset)));
            }
            $fragment->appendChild($this->createReplacement($doc, $entries[$match['index']], $match['text']));

            $entries[$match['index']]['count']++;
            $offset = $match['offset'] + strlen($match['text']);
        }

        if ($fragment === null) {
            return;
        }

        if ($offset < strlen($text)) {
            $fragment->appendChild($textNode->ownerDocument->createTextNode(substr($text, $offset)));
        }

        $textNode->parentNode->replaceChild($fragment, $textNode);
    }

    private function createReplacement(\DOMDocument $doc, array $entry, string $matchedText): \DOMNode
    {
        $node = $doc->createTextNode($matchedText);

        if ($entry['abbr']) {
            $abbr = $doc->createElement('abbr');
            $abbr->setAttribute('class', 'pimcore_glossary');
            $abbr->setAttribute('title', $entry['abbr']);
            $abbr->appendChild($node);
            $node = $abbr;
        }

        if ($entry['link']) {
            $link = $doc->createElement('a');
            $link->setAttribute('class', 'pimcore_glossary');
            $link->setAttribute('href', $entry['link']);
            $link->appendChild($node);
            $node = $link;
        }

        return $node;
    }

    private function getData(string $locale): array
    {
        $siteId = '';
        if (Site::isSiteRequest()) {
            $siteId = Site::getCurrentSite()->getId();
        }

        $cacheKey = 'glossary_entries_' . $locale . '_' . $siteId;

        if (Cache\RuntimeCache::isRegistered($cacheKey)) {
            return Cache\RuntimeCache::get($cacheKey);
        }

        if (!$data = Cache::load($cacheKey)) {
            $list = new Glossary\Listing();
            $list->setCondition("(language = ? OR language IS NULL OR language = '') AND (site = ? OR site IS NULL OR site = '')", [$locale, $siteId]);
            $list->setOrderKey('LENGTH(`text`)', false);
            $list->setOrder('DESC');

            $data = $list->getDataArray();
            $data = $this->prepareData($data);

            Cache::save($data, $cacheKey, ['glossary'], null, 995);
            Cache\RuntimeCache::set($cacheKey, $data);
        }

        return $data;
    }

    private function prepareData(array $data): array
    {
        $mappedData = [];

        foreach ($data as $d) {
            // a pattern with the u modifier does not compile for invalid UTF-8
            if (!$d['text'] || !mb_check_encoding($d['text'], 'UTF-8')) {
                continue;
            }

            $linkType = '';
            $linkTarget = '';

            if ($d['link']) {
                $linkType = 'external';
                $linkTarget = $d['link'];

                if ((int)$d['link']) {
                    if ($doc = Document::getById($d['link'])) {
                        $d['link'] = $doc->getFullPath();

                        $linkType = 'internal';
                        $linkTarget = $doc->getId();
                    }
                }

                // never render javascript:, data: … links, even if they were stored before the save validation
                if (!Glossary::isAllowedLink($d['link'])) {
                    $d['link'] = null;
                    $linkType = '';
                    $linkTarget = '';
                }
            }

            if (!($d['link'] || $d['abbr'])) {
                continue;
            }

            // add PCRE delimiter and modifiers; the u modifier makes the word boundaries and case folding work for
            // non-ASCII letters (Glossar must not match in Glossarübersicht, ÄRZTE must match ärzte)
            if ($d['exactmatch']) {
                $search = '/(?<![\p{L}\p{N}_])' . preg_quote($d['text'], '/') . '(?![\p{L}\p{N}_])/u';
            } else {
                $search = '/' . preg_quote($d['text'], '/') . '/u';
            }

            if (!$d['casesensitive']) {
                $search .= 'i';
            }

            $mappedData[] = [
                'search' => $search,
                'text' => $d['text'],
                'abbr' => $d['abbr'],
                'link' => $d['link'],
                'linkType' => $linkType,
                'linkTarget' => $linkTarget,
            ];
        }

        return $mappedData;
    }
}
