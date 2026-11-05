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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\CurrentMessage;

use JsonException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final readonly class CurrentMessageProvider implements CurrentMessageProviderInterface
{
    public function __construct(
        private TranslatorInterface $translator
    )
    {
    }

    public function getTranslationMessages(string $key, array $parameters = [], ?string $domain = null): MessageInterface
    {
        return new TranslationMessage($key, $parameters, $domain, $this->translator);
    }

    public function getPlainMessage(string $message): MessageInterface
    {
        return new PlainMessage($message);
    }

    public function getMessageFromSerializedString(string $message): MessageInterface
    {
        try {
            $decoded = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new PlainMessage($message);
        }

        // plain messages (e.g. raw exception texts) may be valid JSON as well: only the exact shape written by
        // TranslationMessage::getSerializedString() is a translation object
        if (!$this->isTranslationObject($decoded)) {
            return new PlainMessage($message);
        }

        return new TranslationMessage($decoded['key'], $decoded['params'], $decoded['domain'], $this->translator);
    }

    private function isTranslationObject(mixed $decoded): bool
    {
        if (!is_array($decoded) || count($decoded) !== 3
            || !array_key_exists('key', $decoded)
            || !array_key_exists('params', $decoded)
            || !array_key_exists('domain', $decoded)
        ) {
            return false;
        }

        return is_string($decoded['key'])
            && is_array($decoded['params'])
            && (is_string($decoded['domain']) || $decoded['domain'] === null);
    }
}
