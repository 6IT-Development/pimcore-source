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

namespace Pimcore\Model\Tool\Email;

use Pimcore\Model;
use Pimcore\Model\Tool\Email\Blocklist\Dao;

/**
 * @internal
 *
 * @method Dao getDao()
 * @method void delete()
 * @method void save()
 */
class Blocklist extends Model\AbstractModel
{
    protected ?string $address = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    public static function getByAddress(string $addr): ?Blocklist
    {
        try {
            $address = new self();
            $address->getDao()->getByAddress(self::normalizeAddress($addr));

            return $address;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    /**
     * Brings an address into the form it is stored and looked up in: without surrounding spaces, `mailto:` prefix and
     * display name, lower-cased
     *
     * @internal
     */
    public static function normalizeAddress(string $address): string
    {
        $address = trim($address);
        if (preg_match('/<([^<>]*)>$/', $address, $matches)) {
            $address = trim($matches[1]);
        }
        if (stripos($address, 'mailto:') === 0) {
            $address = trim(substr($address, 7));
        }

        return mb_strtolower($address);
    }

    public function setAddress(string $address): void
    {
        $this->address = self::normalizeAddress($address);
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setModificationDate(int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }
}
