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

namespace Pimcore\Model\DataObject\Data;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Pimcore;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Tool\Serialize;

class EncryptedField implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected Data $delegate;

    protected mixed $plain = null;

    /**
     * The stored ciphertext if it could not be decrypted on load (non-strict mode), see getUndecryptableValue()
     */
    protected ?string $undecryptableValue = null;

    public function __construct(Data $delegate, mixed $plain)
    {
        $this->plain = $plain;
        $this->delegate = $delegate;
        $this->markMeDirty();
    }

    public function getDelegate(): Data
    {
        return $this->delegate;
    }

    public function setDelegate(Data $delegate): void
    {
        $this->delegate = $delegate;
    }

    public function getPlain(): mixed
    {
        return $this->plain;
    }

    public function setPlain(mixed $plain): void
    {
        $this->plain = $plain;
        // an explicitly set value replaces the value that could not be decrypted
        $this->undecryptableValue = null;
        $this->markMeDirty();
    }

    /**
     * The stored ciphertext when the value could not be decrypted on load (missing or different key). It is written
     * back unchanged on save as long as no new value is set.
     *
     * @internal
     */
    public function getUndecryptableValue(): ?string
    {
        return $this->undecryptableValue;
    }

    /**
     * @internal
     */
    public function setUndecryptableValue(?string $undecryptableValue): void
    {
        $this->undecryptableValue = $undecryptableValue;
    }

    /**
     * The plain value is serialized encrypted only, together with the delegate and the owner.
     *
     * @throws Exception
     */
    public function __serialize(): array
    {
        $data = [
            'delegate' => $this->delegate,
            '_owner' => $this->_owner,
            '_fieldname' => $this->_fieldname,
            '_language' => $this->_language,
        ];

        if ($this->undecryptableValue !== null) {
            // already encrypted, keep it so a cached object still writes it back
            $data['undecryptableValue'] = $this->undecryptableValue;
        } elseif ($this->plain !== null) {
            $data['encrypted'] = $this->encrypt($this->plain);
        }

        return $data;
    }

    /**
     * @throws Exception when the value cannot be decrypted, so a cache read is treated as a miss
     */
    public function __unserialize(array $data): void
    {
        // payloads written by the former __sleep() (e.g. in versions) carry the mangled names of protected properties
        foreach ($data as $name => $value) {
            if (str_starts_with($name, "\0*\0")) {
                $data[substr($name, 3)] = $value;
            }
        }

        // missing in payloads written by the former __sleep()
        if (isset($data['delegate'])) {
            $this->delegate = $data['delegate'];
        }
        $this->_owner = $data['_owner'] ?? null;
        $this->_fieldname = $data['_fieldname'] ?? null;
        $this->_language = $data['_language'] ?? null;
        $this->undecryptableValue = $data['undecryptableValue'] ?? null;

        if (isset($data['encrypted'])) {
            $plain = $this->decrypt($data['encrypted']);
            if ($plain instanceof OwnerAwareFieldInterface) {
                $plain->_setOwner($this->_owner);
                $plain->_setOwnerFieldname($this->_fieldname);
            }
            $this->plain = $plain;
        }
    }

    /**
     * @throws Exception
     */
    private function encrypt(mixed $plain): string
    {
        if ($plain instanceof OwnerAwareFieldInterface) {
            // clear the owner on a copy to avoid recursion, the live value keeps its owner
            $plain = clone $plain;
            $plain->_setOwner(null);
            $plain->_setOwnerFieldname('');
        }

        try {
            return Crypto::encrypt(Serialize::serialize($plain), $this->loadKey(), true);
        } catch (Exception $e) {
            Logger::error((string)$e);

            throw new Exception('could not encrypt the value of the encrypted field', 0, $e);
        }
    }

    /**
     * @throws Exception
     */
    private function decrypt(string $encrypted): mixed
    {
        try {
            return Serialize::unserialize(Crypto::decrypt($encrypted, $this->loadKey(), true));
        } catch (Exception $e) {
            Logger::error((string)$e);

            throw new Exception('could not decrypt the value of the encrypted field', 0, $e);
        }
    }

    private function loadKey(): Key
    {
        return Key::loadFromAsciiSafeString((string)Pimcore::getContainer()->getParameter('pimcore.encryption.secret'));
    }
}
