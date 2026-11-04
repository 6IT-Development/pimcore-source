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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;

/**
 * @method Dao getDao()
 */
class Link extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface
{
    /**
     * Editable options in the config that are not attributes of the <a> tag
     */
    private const array CONFIG_OPTIONS = [
        'reload', 'required', 'allowedTypes', 'allowedTargets', 'textPrefix', 'textSuffix', 'noText', 'disabledFields',
    ];

    /**
     * Link data that is not an attribute of the <a> tag (target, title, rel, ... are)
     */
    private const array NON_ATTRIBUTE_DATA = [
        'path', 'text', 'linktype', 'internal', 'internalType', 'internalId', 'parameters', 'anchor', 'attributes', 'type',
    ];

    /**
     * Contains the data for the link
     *
     * @internal
     *
     */
    protected ?array $data = null;

    public function getType(): string
    {
        return 'link';
    }

    public function getData(): ?array
    {
        // update path if internal link
        $this->updatePathFromInternal(true);

        return $this->data;
    }

    public function getDataEditmode(): ?array
    {
        // update path if internal link
        $this->updatePathFromInternal(true, true);

        return $this->data;
    }

    protected function getEditmodeElementClasses(array $options = []): array
    {
        // we don't want the class attribute being applied to the editable container element (<div>, only to the <a> tag inside
        // the default behavior of the parent method is to include the "class" attribute
        return [
            'pimcore_editable',
            'pimcore_editable_' . $this->getType(),
        ];
    }

    public function frontend(): string
    {
        $url = $this->getHref();

        if (strlen($url) > 0) {
            // work on a copy, the options must survive for the next render of the same instance
            $config = $this->config;
            $prefix = '';
            $suffix = '';
            $noText = false;
            $disabledText = false;

            if (array_key_exists('textPrefix', $config)) {
                $prefix = $config['textPrefix'];
            }

            if (array_key_exists('textSuffix', $config)) {
                $suffix = $config['textSuffix'];
            }

            if (isset($config['noText']) && $config['noText']) {
                $noText = true;
            }

            if (array_key_exists('disabledFields', $config) && is_array($config['disabledFields'])) {
                $disabledText = in_array('text', $config['disabledFields'], true);
            }

            // the editable options and the link data that is not an attribute of the <a> tag
            $config = array_diff_key($config, array_flip(self::CONFIG_OPTIONS));
            $this->data = is_array($this->data) ? $this->data : [];
            $data = array_diff_key($this->data, array_flip(self::NON_ATTRIBUTE_DATA));
            $availableAttribs = array_merge($data, $config);

            // add attributes to link
            $attribs = [];
            foreach ($availableAttribs as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    if (!empty($data[$key]) && !empty($config[$key])) {
                        $attribs[] = $key . '="' . htmlspecialchars((string) $data[$key]) . ' ' . htmlspecialchars((string) $config[$key]) . '"';
                    } elseif ($value) {
                        $attribs[] = (is_string($value)) ?
                            $key . '="' . htmlspecialchars($value) . '"' :
                            $key . '="' . $value . '"';
                    }
                }
            }
            $attribs = array_unique($attribs);

            $text = '';
            if (!$noText) {
                $text = htmlspecialchars($disabledText ? $url : ($this->data['text'] ?? $url));
            }

            // parameters and anchor are already escaped by getHref(), so existing entities are not encoded again
            $href = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);

            return '<a href="' . $href . '" ' . implode(' ', $attribs) . '>' . $prefix . $text . $suffix . '</a>';
        }

        return '';
    }

    public function checkValidity(): bool
    {
        $sane = true;
        if (is_array($this->data) && isset($this->data['internal']) && $this->data['internal']) {
            if ($this->data['internalType'] == 'document') {
                $doc = Document::getById($this->data['internalId']);
                if (!$doc) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent document with id [' . $this->data['internalId'] . '] in document [' . $this->getDocumentId() . ']'
                    );
                    $this->data = null;
                }
            } elseif ($this->data['internalType'] == 'asset') {
                $asset = Asset::getById($this->data['internalId']);
                if (!$asset) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent asset with id [' . $this->data['internalId'] . '] in document [' . $this->getDocumentId() . ']'
                    );
                    $this->data = null;
                }
            } elseif ($this->data['internalType'] == 'object') {
                $object = Model\DataObject\Concrete::getById($this->data['internalId']);
                if (!$object) {
                    $sane = false;
                    Logger::notice(
                        'Detected insane relation, removing reference to non existent object with id [' . $this->data['internalId'] . '] in document [' . $this->getDocumentId() . ']'
                    );
                    $this->data = null;
                }
            }
        }

        return $sane;
    }

    public function getHref(): string
    {
        $this->updatePathFromInternal();

        $url = $this->data['path'] ?? '';

        if (strlen($this->data['parameters'] ?? '') > 0) {
            $url .= (str_contains($url, '?') ? '&' : '?') . htmlspecialchars(str_replace('?', '', $this->getParameters()));
        }

        if (strlen($this->data['anchor'] ?? '') > 0) {
            $anchor = str_replace('"', urlencode('"'), htmlspecialchars($this->getAnchor()));
            $url .= '#' . str_replace('#', '', $anchor);
        }

        return $url;
    }

    private function updatePathFromInternal(bool $realPath = false, bool $editmode = false): void
    {
        $method = 'getFullPath';
        if ($realPath) {
            $method = 'getRealFullPath';
        }

        if (isset($this->data['internal']) && $this->data['internal']) {
            if ($this->data['internalType'] == 'document') {
                if ($doc = Document::getById($this->data['internalId'])) {
                    if ($editmode || (!Document::doHideUnpublished() || $doc->isPublished())) {
                        $this->data['path'] = $doc->$method();
                    } else {
                        $this->data['path'] = '';
                    }
                }
            } elseif ($this->data['internalType'] == 'asset') {
                if ($asset = Asset::getById($this->data['internalId'])) {
                    $this->data['path'] = $asset->$method();
                }
            } elseif ($this->data['internalType'] == 'object') {
                if ($object = Model\DataObject::getById($this->data['internalId'])) {
                    if ($editmode) {
                        $this->data['path'] = $object->getFullPath();
                    } else {
                        if ($object instanceof Model\DataObject\Concrete) {
                            if ($linkGenerator = $object->getClass()->getLinkGenerator()) {
                                if ($realPath) {
                                    $this->data['path'] = $object->getFullPath();
                                } else {
                                    $this->data['path'] = $linkGenerator->generate(
                                        $object,
                                        [
                                            'document' => $this->getDocument(),
                                            'context' => $this,
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        // deletes unnecessary attribute, which was set by mistake in earlier versions, see also
        // https://github.com/pimcore/pimcore/issues/7394
        if (isset($this->data['type'])) {
            unset($this->data['type']);
        }
    }

    public function getText(): string
    {
        return $this->data['text'] ?? '';
    }

    public function setText(string $text): void
    {
        $this->data['text'] = $text;
    }

    public function getTarget(): string
    {
        return $this->data['target'] ?? '';
    }

    public function getParameters(): string
    {
        return $this->data['parameters'] ?? '';
    }

    public function getAnchor(): string
    {
        return $this->data['anchor'] ?? '';
    }

    public function getTitle(): string
    {
        return $this->data['title'] ?? '';
    }

    public function getRel(): string
    {
        return $this->data['rel'] ?? '';
    }

    public function getTabindex(): string
    {
        return $this->data['tabindex'] ?? '';
    }

    public function getAccesskey(): string
    {
        return $this->data['accesskey'] ?? '';
    }

    public function getClass(): mixed
    {
        return $this->data['class'] ?? '';
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data);
        $this->data = $unserializedData;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        if (!is_array($data)) {
            $data = [];
        }

        $path = $data['path'] ?? null;

        if (!empty($path)) {
            $target = null;

            if ($data['linktype'] == 'internal' && $data['internalType']) {
                $target = Model\Element\Service::getElementByPath($data['internalType'], $path);
                if ($target) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                }
            }

            if (!$target) {
                if ($target = Document::getByPath($path)) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                    $data['internalType'] = 'document';
                } elseif ($target = Asset::getByPath($path)) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                    $data['internalType'] = 'asset';
                } elseif ($target = Model\DataObject\Concrete::getByPath($path)) {
                    $data['internal'] = true;
                    $data['internalId'] = $target->getId();
                    $data['internalType'] = 'object';
                } else {
                    $data['internal'] = false;
                    $data['internalId'] = null;
                    $data['internalType'] = null;
                    $data['linktype'] = 'direct';
                }

                if ($target) {
                    $data['linktype'] = 'internal';
                }
            }
        }

        $this->data = $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return strlen($this->getHref()) < 1;
    }

    public function resolveDependencies(): array
    {
        $dependencies = [];
        $isInternal = $this->data['internal'] ?? false;

        if (is_array($this->data) && $isInternal) {
            if ((int)$this->data['internalId'] > 0) {
                if ($this->data['internalType'] == 'document') {
                    if ($doc = Document::getById($this->data['internalId'])) {
                        $key = 'document_' . $doc->getId();

                        $dependencies[$key] = [
                            'id' => $doc->getId(),
                            'type' => 'document',
                        ];
                    }
                } elseif ($this->data['internalType'] == 'asset') {
                    if ($asset = Asset::getById($this->data['internalId'])) {
                        $key = 'asset_' . $asset->getId();

                        $dependencies[$key] = [
                            'id' => $asset->getId(),
                            'type' => 'asset',
                        ];
                    }
                } elseif ($this->data['internalType'] == 'object') {
                    if ($object = DataObject\Concrete::getById($this->data['internalId'])) {
                        $key = 'object_' . $object->getId();

                        $dependencies[$key] = [
                            'id' => $object->getId(),
                            'type' => 'object',
                        ];
                    }
                }
            }
        }

        return $dependencies;
    }

    public function rewriteIds(array $idMapping): void
    {
        if (isset($this->data['internal']) && $this->data['internal']) {
            $type = $this->data['internalType'];
            $id = (int)$this->data['internalId'];

            if (array_key_exists($type, $idMapping)) {
                if (array_key_exists($id, $idMapping[$type])) {
                    $this->data['internalId'] = $idMapping[$type][$id];
                    $this->getHref();
                }
            }
        }
    }
}
