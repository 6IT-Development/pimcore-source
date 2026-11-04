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

namespace Pimcore\Tests\Unit\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\ContentTemplateListener;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\Request\Resolver\TemplateResolver;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Template parameters of documents with a custom template (B-40).
 */
class ContentTemplateListenerTest extends TestCase
{
    private const string TEMPLATE = 'content.html.twig';

    public function testArgumentsAndResultArePassedWithTheResultWinning(): void
    {
        $this->assertSame('slug=from-result page=2 title=-', $this->render(['slug' => 'from-result']));
    }

    public function testNullResultPassesTheControllerArguments(): void
    {
        $this->assertSame('slug=from-argument page=2 title=-', $this->render(null));
    }

    public function testListVarsSelectTheControllerArguments(): void
    {
        $this->assertSame('slug=from-argument page=- title=-', $this->render(null, new Template(self::TEMPLATE, vars: ['slug'])));
        $this->assertSame('slug=- page=- title=-', $this->render(null, new Template(self::TEMPLATE, vars: [])));
    }

    public function testNamedVarsKeepAllControllerArguments(): void
    {
        $this->assertSame(
            'slug=from-argument page=2 title=Hello',
            $this->render(null, new Template(self::TEMPLATE, vars: ['title' => 'Hello']))
        );
    }

    public function testBlockOnlyRendersTheBlock(): void
    {
        $this->assertSame('block:from-result', $this->render(['slug' => 'from-result'], new Template(self::TEMPLATE, block: 'teaser')));
    }

    public function testNothingHappensWithoutAContentTemplate(): void
    {
        $event = $this->createEvent(null, null, false);
        $this->createListener()->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    private function render(?array $controllerResult, ?Template $attribute = null): string
    {
        $event = $this->createEvent($controllerResult, $attribute);
        $this->createListener()->onKernelView($event);

        $this->assertNotNull($event->getResponse());

        return trim((string) $event->getResponse()->getContent());
    }

    private function createListener(): ContentTemplateListener
    {
        $twig = new Environment(new ArrayLoader([
            self::TEMPLATE => 'slug={{ slug|default("-") }} page={{ page|default("-") }} title={{ title|default("-") }}'
                . '{% if false %}{% block teaser %}block:{{ slug|default("-") }}{% endblock %}{% endif %}',
        ]));

        $contextResolver = $this->createMock(PimcoreContextResolver::class);
        $contextResolver->method('matchesPimcoreContext')->willReturn(true);

        $listener = new ContentTemplateListener(new TemplateResolver(new RequestStack()), $twig);
        $listener->setPimcoreContextResolver($contextResolver);

        return $listener;
    }

    private function createEvent(?array $controllerResult, ?Template $attribute, bool $withContentTemplate = true): ViewEvent
    {
        $request = new Request();
        if ($withContentTemplate) {
            (new TemplateResolver(new RequestStack()))->setTemplate($request, self::TEMPLATE);
        }
        if ($attribute) {
            $request->attributes->set('_template', $attribute);
        }

        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = static fn (string $slug, int $page): array => [];
        $argumentsEvent = new ControllerArgumentsEvent($kernel, $controller, ['from-argument', 2], $request, HttpKernelInterface::MAIN_REQUEST);

        return new ViewEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $controllerResult, $argumentsEvent);
    }
}
