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

namespace Pimcore\Model;

use Pimcore\Cache;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Property\Dao;

/**
 * @method Dao getDao()
 * @method void save()
 */
final class Property extends AbstractModel
{
    /**
     * The values of the `properties.type` column
     */
    public const array TYPES = ['text', 'document', 'asset', 'object', 'bool', 'select'];

    protected ?string $name = null;

    protected mixed $data = null;

    protected ?string $type = null;

    protected ?string $ctype = null;

    protected ?string $cpath = null;

    protected ?int $cid = null;

    protected bool $inheritable = false;

    protected bool $inherited = false;

    /**
     * @return $this
     * @internal
     *
     */
    public function setDataFromEditmode(mixed $data): Property
    {
        // IMPORTANT: if you use this method be sure that the type of the property is already set

        if (in_array($this->getType(), ['document', 'asset', 'object'])) {
            $this->data = null;
            if ((string)$data !== '') {
                $el = Element\Service::getElementByPath($this->getType(), (string)$data);
                if (!$el) {
                    throw new \InvalidArgumentException(sprintf('Property "%s": there is no %s "%s"', $this->getName(), $this->getType(), $data));
                }
                $this->data = $el->getId();
            }
        } elseif ($this->type == 'bool') {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        } else {
            // plain text
            $this->data = $data;
        }

        return $this;
    }

    /**
     * @return $this
     * @internal
     *
     */
    public function setDataFromResource(mixed $data): Property
    {
        // IMPORTANT: if you use this method be sure that the type of the property is already set
        // do not set data for object, asset and document here, this is loaded dynamically when calling $this->getData();
        if ($this->type == 'bool') {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        } else {
            // plain text
            $this->data = $data;
        }

        return $this;
    }

    public function save(): void
    {
        $this->getDao()->save();

        if ($this->getInheritable()) {
            // descendants cache the inherited property under their own keys
            Cache::clearTag($this->getCtype() . '_properties');
        } else {
            Cache::remove($this->getCtype() . '_properties_' . $this->getCid());
        }
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    /**
     * enum('document','asset','object')
     */
    public function getCtype(): ?string
    {
        return $this->ctype;
    }

    public function getData(): mixed
    {
        // lazy-load data of type asset, document, object
        if (in_array($this->getType(), ['document', 'asset', 'object']) && !$this->data instanceof ElementInterface && is_numeric($this->data)) {
            return Element\Service::getElementById($this->getType(), (int)$this->data);
        }

        return $this->data;
    }

    /**
     * Returns the stored id of a relation property without loading the element, so that a missing
     * target does not turn into NULL
     *
     * @internal
     */
    public function getRelationId(): ?int
    {
        if ($this->data instanceof ElementInterface) {
            return $this->data->getId();
        }

        return is_numeric($this->data) ? (int)$this->data : null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * enum('text','document','asset','object','bool','select')
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setCid(int $cid): Property
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * enum('document','asset','object')
     *
     *
     * @return $this
     */
    public function setCtype(string $ctype): Property
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @return $this
     */
    public function setData(mixed $data): Property
    {
        if ($data instanceof ElementInterface) {
            $elementType = Service::getElementType($data);
            if (in_array($this->type, ['document', 'asset', 'object'], true) && $this->type !== $elementType) {
                throw new \InvalidArgumentException(sprintf('Property "%s" of type "%s" cannot hold %s %s', $this->name, $this->type, $elementType === 'asset' ? 'an' : 'a', $elementType));
            }
            $this->setType($elementType);
            $data = $data->getId();
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @return $this
     */
    public function setName(string $name): Property
    {
        $this->name = $name;

        return $this;
    }

    /**
     * enum('text','document','asset','object','bool','select')
     *
     *
     * @return $this
     */
    public function setType(string $type): Property
    {
        $this->type = $type;

        return $this;
    }

    public function getCpath(): ?string
    {
        return $this->cpath;
    }

    public function getInherited(): bool
    {
        return $this->inherited;
    }

    /**
     * Alias for getInherited()
     *
     */
    public function isInherited(): bool
    {
        return $this->getInherited();
    }

    /**
     * @return $this
     */
    public function setCpath(?string $cpath): Property
    {
        $this->cpath = $cpath;

        return $this;
    }

    /**
     * @return $this
     */
    public function setInherited(bool $inherited): Property
    {
        $this->inherited = $inherited;

        return $this;
    }

    public function getInheritable(): bool
    {
        return $this->inheritable;
    }

    /**
     * @return $this
     */
    public function setInheritable(bool $inheritable): Property
    {
        $this->inheritable = $inheritable;

        return $this;
    }

    /**
     * @internal
     *
     */
    public function resolveDependencies(): array
    {
        $dependencies = [];

        if ($this->getData() instanceof ElementInterface) {
            $elementType = Element\Service::getElementType($this->getData());
            $key = $elementType . '_' . $this->getData()->getId();
            $dependencies[$key] = [
                'id' => $this->getData()->getId(),
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     *
     * @internal
     */
    public function rewriteIds(array $idMapping): void
    {
        if (!$this->isInherited()) {
            $id = $this->getRelationId();
            if ($id !== null && isset($idMapping[$this->getType()][$id])) {
                // keep the old target when the mapped element does not exist
                $target = Element\Service::getElementById($this->getType(), $idMapping[$this->getType()][$id]);
                if ($target) {
                    $this->setData($target);
                }
            }
        }
    }

    /**
     * @internal
     *
     */
    public function serialize(): array
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'data' => $this->getData(),
        ];
    }
}
