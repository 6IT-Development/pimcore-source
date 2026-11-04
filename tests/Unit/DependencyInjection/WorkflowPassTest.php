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

namespace Pimcore\Tests\Unit\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\WorkflowPass;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\Manager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class WorkflowPassTest extends TestCase
{
    /**
     * B-28: the custom HTML service of a transition is applied to that transition only.
     */
    public function testCustomHtmlServiceIsAppliedToItsTransitionOnly(): void
    {
        $container = $this->createContainer([
            'first' => ['from' => ['a'], 'to' => ['b'], 'options' => []],
            'second' => [
                'from' => ['b'],
                'to' => ['c'],
                'options' => ['notes' => ['customHtml' => ['service' => 'App\CustomHtml', 'position' => 'top']]],
            ],
        ]);

        new WorkflowPass()->process($container);

        $transitions = $container->getDefinition('workflow.test.definition')->getArgument(1);
        $this->assertCount(2, $transitions);

        [$first, $second] = $transitions;
        $this->assertSame('first', $first->getArgument(0));
        $this->assertFalse($first->hasMethodCall('setCustomHtmlService'));

        $this->assertSame('second', $second->getArgument(0));
        $calls = $second->getMethodCalls();
        $this->assertCount(1, $calls);
        $this->assertSame('setCustomHtmlService', $calls[0][0]);

        $customHtml = $calls[0][1][0];
        $this->assertInstanceOf(Definition::class, $customHtml);
        $this->assertSame('App\CustomHtml', $customHtml->getClass());
        $this->assertSame(['second', false, 'top'], $customHtml->getArguments());
    }

    /**
     * B-29: the audit trail listener logs to the workflow channel and listens to the events of its workflow.
     */
    public function testAuditTrailListenerUsesTheWorkflowChannel(): void
    {
        $container = $this->createContainer(
            ['go' => ['from' => ['a'], 'to' => ['b'], 'options' => []]],
            ['audit_trail' => ['enabled' => true]]
        );

        new WorkflowPass()->process($container);

        $listener = $container->getDefinition('.workflow.test.listener.audit_trail');
        $this->assertSame([['channel' => 'workflow']], $listener->getTag('monolog.logger'));
        $this->assertSame(
            ['workflow.test.leave', 'workflow.test.transition', 'workflow.test.enter'],
            array_column($listener->getTag('kernel.event_listener'), 'event')
        );
    }

    public function testNoAuditTrailListenerIfDisabled(): void
    {
        $container = $this->createContainer(['go' => ['from' => ['a'], 'to' => ['b'], 'options' => []]]);

        new WorkflowPass()->process($container);

        $this->assertFalse($container->hasDefinition('.workflow.test.listener.audit_trail'));
        $this->assertTrue($container->hasDefinition('workflow.test'));
    }

    public function testStateMachineGetsOneTransitionPerFromAndTo(): void
    {
        $container = $this->createContainer(
            ['go' => ['from' => ['a', 'b'], 'to' => ['c'], 'options' => []]],
            ['type' => 'state_machine']
        );

        new WorkflowPass()->process($container);

        $transitions = $container->getDefinition('state_machine.test.definition')->getArgument(1);
        $this->assertSame(
            [['go', 'a', 'c'], ['go', 'b', 'c']],
            array_map(static fn (Definition $transition): array => array_slice($transition->getArguments(), 0, 3), $transitions)
        );
    }

    private function createContainer(array $transitions, array $workflowConfig = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(Manager::class, new Definition(Manager::class));
        $container->setParameter('pimcore.workflow', [
            'test' => $workflowConfig + [
                'enabled' => true,
                'priority' => 1,
                'type' => 'workflow',
                'places' => ['a' => [], 'b' => [], 'c' => []],
                'transitions' => $transitions,
                'supports' => ['stdClass'],
                'audit_trail' => ['enabled' => false],
            ],
        ]);

        return $container;
    }
}
