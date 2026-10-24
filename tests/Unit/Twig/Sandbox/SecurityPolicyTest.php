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

namespace Pimcore\Tests\Unit\Twig\Sandbox;

use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

class SecurityPolicyTest extends TestCase
{
    public function testGettersAndToStringAreAllowed(): void
    {
        $policy = new SecurityPolicy();
        $obj = new SandboxTestObject();

        $policy->checkMethodAllowed($obj, 'getName');
        $policy->checkMethodAllowed($obj, 'isActive');
        $policy->checkMethodAllowed($obj, 'hasName');
        $policy->checkMethodAllowed($obj, '__toString');
        $policy->checkPropertyAllowed($obj, 'title');

        $this->addToAssertionCount(5);
    }

    public function testOtherMethodsAreDenied(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);

        new SecurityPolicy()->checkMethodAllowed(new SandboxTestObject(), 'save');
    }

    public function testConfiguredMethodsAreAllowed(): void
    {
        $policy = new SecurityPolicy([], [], [], [SandboxTestObject::class => ['Save']]);

        $policy->checkMethodAllowed(new SandboxTestObject(), 'save');

        $this->addToAssertionCount(1);
    }

    public function testDeniedClassesWinOverAllowlist(): void
    {
        $policy = new SecurityPolicy([], [], [], [Container::class => ['get']]);

        $this->expectException(SecurityNotAllowedMethodError::class);

        $policy->checkMethodAllowed(new Container(), 'getParameter');
    }

    public function testPropertiesOfDeniedClassesAreDenied(): void
    {
        $this->expectException(SecurityNotAllowedPropertyError::class);

        new SecurityPolicy()->checkPropertyAllowed(new Container(), 'parameters');
    }

    public function testUserPasswordGetterIsDenied(): void
    {
        $this->expectException(SecurityNotAllowedMethodError::class);

        new SecurityPolicy()->checkMethodAllowed(new User(), 'getPassword');
    }

    public function testSandboxedTemplateCannotUseContainer(): void
    {
        $twig = new Environment(new ArrayLoader([
            'secret' => "{{ container.getParameter('kernel.secret') }}",
            'name' => '{{ obj.name }}',
        ]));
        $twig->addExtension(new SandboxExtension(new SecurityPolicy(), true));

        $this->assertSame('foo', $twig->render('name', ['obj' => new SandboxTestObject()]));

        $container = new Container();
        $container->setParameter('kernel.secret', 's3cr3t');

        $this->expectException(SecurityNotAllowedMethodError::class);
        $twig->render('secret', ['container' => $container]);
    }
}

class SandboxTestObject
{
    public string $title = 'bar';

    public function getName(): string
    {
        return 'foo';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function hasName(): bool
    {
        return true;
    }

    public function save(): void
    {
    }

    public function __toString(): string
    {
        return 'foo';
    }
}
