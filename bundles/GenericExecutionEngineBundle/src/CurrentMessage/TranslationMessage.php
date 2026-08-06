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
final readonly class TranslationMessage implements MessageInterface
{
    public function __construct(
        private string              $key,
        private array               $params,
        private string              $domain,
        private TranslatorInterface $translator
    )
    {
    }

    public function getSerializedString(): string
    {
        try {
            return json_encode($this->buildMessageArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '';
        }
    }

    private function buildMessageArray(): array
    {
        return [
            'key' => $this->key,
            'params' => $this->params,
            'domain' => $this->domain,
        ];
    }

    public function getMessage(): string
    {
        return $this->translator->trans($this->key, $this->params, $this->domain);
    }
}
