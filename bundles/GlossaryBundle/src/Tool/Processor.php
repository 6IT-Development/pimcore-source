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

use Doctrine\DBAL\Exception\TableNotFoundException;
use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Cache;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Logger;
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
        // without a request (commands, messenger workers, e-mail rendering) there is no locale and no current page;
        // parse() can be called with explicit values instead
        if (!$this->requestHelper->hasMainRequest()) {
            return $content;
        }

        if ($this->editmodeResolver->isEditmode()) {
            return $content;
        }

        // the language of the rendered content (e.g. a snippet in another language) is the one of the current
        // request, while links to the page the visitor is on are suppressed, i.e. the document of the main request
        $mainRequest = $this->requestHelper->getMainRequest();
        $locale = $this->requestHelper->getCurrentRequest()->getLocale();
        $currentDocument = $this->documentResolver->getDocument($mainRequest);

        return $this->parse($content, $options, $locale, $currentDocument, $mainRequest->getPathInfo());
    }

    /**
     * @param Document|null $document the page the content is shown on, never linked to itself
     * @param string|null $uri the path of that page; a query string, fragment and trailing slash are ignored
     */
    public function parse(string $content, array $options, string $locale, ?Document $document, ?string $uri): string
    {
        $data = $this->getData($locale);
        if (empty($data)) {
            return $content;
        }

        $options = array_merge([
            'limit' => -1,
        ], $options);

        $path = $uri === null ? null : rtrim((string)parse_url($uri, PHP_URL_PATH), '/');

        $entries = [];
        foreach ($data as $entry) {
            if ($this->isLinkToCurrentPage($entry, $document, $path)) {
                continue;
            }

            $entry['count'] = 0;
            $entries[] = $entry;
        }

        if (!$entries) {
            return $content;
        }

        // only text nodes are searched, so terms are never replaced inside tag names, attribute values or markup
        // inserted for another term, and existing links and abbreviations are never nested, whatever blocked_tags says;
        // the <head> of a full document (e.g. its <title>) is never processed
        $html = new DomCrawler($content);
        $root = $html->getNode(0);
        $textNodes = (new \DOMXPath($root->ownerDocument))
            ->query('//text()[normalize-space()][not(ancestor::a or ancestor::abbr or ancestor::head)]');

        foreach (iterator_to_array($textNodes) as $textNode) {
            if ($this->hasBlockedAncestor($textNode)) {
                continue;
            }

            $this->replaceInTextNode($textNode, $entries, (int)$options['limit']);
        }

        if (preg_match('@</html>@i', $content)) {
            $result = $this->serializeDocument($content, $root);
        } else {
            $this->removeImpliedHead($content, $root);
            $result = $html->html();
        }
        $html->clear();

        return $result;
    }

    private function isLinkToCurrentPage(array $entry, ?Document $document, ?string $path): bool
    {
        if ($entry['linkType'] === '') {
            return false;
        }

        // the rendered link: the full path of the target document for internal links
        $linkPath = rtrim((string)$entry['link'], ' /');

        if ($document instanceof Document) {
            if ($entry['linkType'] === 'internal' && $document->getId() === $entry['linkTarget']) {
                return true;
            }

            if (rtrim($document->getFullPath(), '/') === $linkPath) {
                return true;
            }
        }

        return $path === $linkPath;
    }

    /**
     * DomCrawler::html() returns the inner HTML of the <html> element, which drops the doctype (quirks mode) and the
     * attributes of <html> (lang, dir, class) when the filter is applied around a whole layout.
     */
    private function serializeDocument(string $content, \DOMNode $root): string
    {
        $doctype = '';
        if (preg_match('/^(?:\xEF\xBB\xBF)?\s*(<!doctype[^>]*>)/i', $content, $matches)) {
            $doctype = $matches[1];
        }

        return $doctype . $root->ownerDocument->saveHTML($root);
    }

    /**
     * The HTML5 parser of PHP 8.4 adds an empty <head> in front of a <body>…</body> input, which would end up inside
     * the page when the filter is applied around the body of a layout.
     */
    private function removeImpliedHead(string $content, \DOMNode $root): void
    {
        $head = $root->firstChild;
        if ($head instanceof \DOMElement && strtolower($head->nodeName) === 'head' && !$head->hasChildNodes()
            && !preg_match('@<head[\s>]@i', $content)
        ) {
            $root->removeChild($head);
        }
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

            try {
                $data = $list->getDataArray();
            } catch (TableNotFoundException $e) {
                // the bundle is enabled but not installed (or was uninstalled while templates still use the filter);
                // remembered for the rest of the request, so this is logged once per request instead of per block
                Logger::warning('Glossary table not found, run pimcore:bundle:install PimcoreGlossaryBundle: ' . $e->getMessage());
                Cache\RuntimeCache::set($cacheKey, []);

                return [];
            }

            $tags = ['glossary'];
            $data = $this->prepareData($data, $tags);

            Cache::save($data, $cacheKey, array_unique($tags), null, 995);
            Cache\RuntimeCache::set($cacheKey, $data);
        }

        return $data;
    }

    /**
     * @param string[] $tags receives the cache tags of the documents the entries link to
     */
    private function prepareData(array $data, array &$tags): array
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
                        // the cached entries embed the path and published state of the target, so moving, renaming,
                        // (un)publishing or deleting it invalidates them
                        $tags[] = $doc->getCacheTag();

                        if ($doc->isPublished()) {
                            $d['link'] = $doc->getFullPath();

                            $linkType = 'internal';
                            $linkTarget = $doc->getId();
                        } else {
                            // never link to a page that returns 404, keep the abbreviation only
                            $d['link'] = null;
                            $linkType = '';
                            $linkTarget = '';
                        }
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
