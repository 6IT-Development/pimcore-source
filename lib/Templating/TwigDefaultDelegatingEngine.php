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

namespace Pimcore\Templating;

use Exception;
use Pimcore\Config;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * @internal
 */
class TwigDefaultDelegatingEngine
{
    protected bool $delegate = false;

    public function __construct(protected Environment $twig, protected Config $config)
    {
    }

    public function exists(string $name): bool
    {
        return $this->twig->getLoader()->exists($name);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function render(string $name, array $parameters = []): string
    {
        return $this->twig->render($name, $parameters);
    }

    public function supports(string $name): bool
    {
        return true;
    }

    public function setDelegate(bool $delegate): void
    {
        $this->delegate = $delegate;
    }

    /**
     * @return bool $delegate
     */
    public function isDelegate(): bool
    {
        return $this->delegate;
    }

    public function getTwigEnvironment(bool $sandboxed = false): Environment
    {
        if ($sandboxed) {
            /** @var SandboxExtension $sandboxExtension */
            $sandboxExtension = $this->twig->getExtension(SandboxExtension::class);
            $sandboxExtension->enableSandbox();
        }

        return $this->twig;
    }

    public function disableSandboxExtensionFromTwigEnvironment(): void
    {
        /** @var SandboxExtension $sandboxExtension */
        $sandboxExtension = $this->twig->getExtension(SandboxExtension::class);
        $sandboxExtension->disableSandbox();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function renderResponse(string $view, array $parameters = [], ?Response $response = null): Response
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }

    public function stream(string $view, array $parameters = []): void
    {
        $this->twig->display($view, $parameters);
    }
}
