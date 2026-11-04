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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Component\Workflow;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Validator\StateMachineValidator;
use Symfony\Component\Workflow\Validator\WorkflowValidator;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Registers the workflows of the pimcore.workflows config like FrameworkBundle registers framework.workflows.
 *
 * @internal
 */
final class WorkflowPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // FrameworkBundle defines workflow.registry and the abstract workflow services whenever symfony/workflow is
        // installed (a Pimcore dependency), unless the app sets framework.workflows: false. Only then the fallback
        // definitions are loaded here; autoconfiguration has already run, so they must not rely on it.
        if (!$container->hasDefinition('workflow.registry')) {
            $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../../config'));
            $loader->load('services_symfony_workflow.yaml');
        }

        $workflowManagerDefinition = $container->getDefinition(Manager::class);

        foreach ($container->getParameter('pimcore.workflow') as $workflowName => $workflowConfig) {
            if ($workflowConfig['enabled']) {
                $this->registerWorkflow($container, $workflowManagerDefinition, (string) $workflowName, $workflowConfig);
            }
        }
    }

    private function registerWorkflow(
        ContainerBuilder $container,
        Definition $workflowManagerDefinition,
        string $workflowName,
        array $workflowConfig
    ): void {
        $type = $workflowConfig['type'] ?? 'workflow';
        $workflowId = sprintf('%s.%s', $type, $workflowName);
        $definitionId = sprintf('%s.definition', $workflowId);

        $workflowManagerDefinition->addMethodCall('registerWorkflow', [
            $workflowName,
            [
                'label' => $workflowConfig['label'] ?? $workflowName,
                'priority' => $workflowConfig['priority'],
                'type' => $type,
            ],
        ]);

        $transitions = $this->createTransitions($type, $workflowConfig);

        $places = [];
        foreach ($workflowConfig['places'] as $place => $placeConfig) {
            $places[] = $place;

            $workflowManagerDefinition->addMethodCall('addPlaceConfig', [$workflowName, $place, $placeConfig]);
        }

        $this->validateDefinition($workflowName, $type, $places, $workflowConfig);

        foreach ($workflowConfig['globalActions'] ?? [] as $action => $actionConfig) {
            $customHtmlServiceDefinition = isset($actionConfig['notes']['customHtml'])
                ? $this->createCustomHtmlService($actionConfig['notes']['customHtml'], $action, true)
                : null;
            $workflowManagerDefinition->addMethodCall('addGlobalAction', [$workflowName, $action, $actionConfig, $customHtmlServiceDefinition]);
        }

        $markingStoreType = $workflowConfig['marking_store']['type'] ?? null;
        $markingStoreService = $workflowConfig['marking_store']['service'] ?? null;
        if ($markingStoreService === null && $markingStoreType === null) {
            $markingStoreType = 'state_table';
        }

        $definitionDefinition = new Definition(Workflow\Definition::class);
        $definitionDefinition->setPublic(false);
        $definitionDefinition->addArgument($places);
        $definitionDefinition->addArgument($transitions);
        $definitionDefinition->addTag('workflow.definition', [
            'name' => $workflowName,
            'type' => $type,
            'marking_store' => $markingStoreType,
        ]);

        if (!empty($workflowConfig['initial_markings'])) {
            $definitionDefinition->addArgument($workflowConfig['initial_markings']);
        }

        $workflowDefinition = new ChildDefinition(sprintf('%s.abstract', $type));
        $workflowDefinition->replaceArgument(0, new Reference($definitionId));
        $markingStore = $this->createMarkingStore($workflowName, $places, $workflowConfig, $markingStoreType, $markingStoreService);
        if ($markingStore !== null) {
            $workflowDefinition->replaceArgument(1, $markingStore);
        }
        $workflowDefinition->setPublic(true);
        // the tags of FrameworkBundle: profiler panel, TraceableWorkflow in debug, Symfony's workflow:dump.
        // No definition_validators: WorkflowValidatorPass would instantiate the definition before autowiring
        // (custom HTML services of the transitions), validateDefinition() checks the config instead.
        $workflowDefinition->addTag('workflow', [
            'name' => $workflowName,
            'metadata' => [],
            'definition_validators' => [],
            'definition_id' => $definitionId,
        ]);
        $workflowDefinition->addTag('workflow' === $type ? 'workflow.workflow' : 'workflow.state_machine', ['name' => $workflowName]);
        // the Pimcore workflows only, indexed by name for pimcore:workflow:dump
        $workflowDefinition->addTag('pimcore.workflow', ['name' => $workflowName]);
        $workflowDefinition->replaceArgument(3, $workflowName);
        $workflowDefinition->replaceArgument(4, $workflowConfig['events_to_dispatch'] ?? null);

        $container->setDefinition($workflowId, $workflowDefinition);
        $container->setDefinition($definitionId, $definitionDefinition);
        // WorkflowInterface $fooWorkflow / $fooStateMachine or #[Target('foo')]
        $container->registerAliasForArgument($workflowId, WorkflowInterface::class, $workflowName . '.' . $type, $workflowName);

        $this->registerSupportStrategy($container, $workflowId, $workflowConfig);
        $this->registerAuditTrail($container, $workflowId, $workflowName, $workflowConfig);
        $this->registerGuard($container, $workflowId, $workflowName, $workflowConfig);
    }

    /**
     * @return Definition[] one transition per config entry, for state machines one per from/to combination
     */
    private function createTransitions(string $type, array $workflowConfig): array
    {
        $transitions = [];
        foreach ($workflowConfig['transitions'] as $transitionName => $transitionConfig) {
            // the definitions of this transition only
            $currentTransitions = [];
            if ('workflow' === $type) {
                $currentTransitions[] = new Definition(Transition::class, [
                    $transitionName,
                    $transitionConfig['from'],
                    $transitionConfig['to'],
                    $transitionConfig['options'],
                ]);
            } elseif ('state_machine' === $type) {
                foreach ($transitionConfig['from'] as $from) {
                    foreach ($transitionConfig['to'] as $to) {
                        $currentTransitions[] = new Definition(
                            Transition::class,
                            [$transitionName, $from, $to, $transitionConfig['options']]
                        );
                    }
                }
            }

            if (isset($transitionConfig['options']['notes']['customHtml'])) {
                foreach ($currentTransitions as $transition) {
                    $transition->addMethodCall('setCustomHtmlService', [
                        $this->createCustomHtmlService($transitionConfig['options']['notes']['customHtml'], $transitionName, false),
                    ]);
                }
            }

            array_push($transitions, ...$currentTransitions);
        }

        return $transitions;
    }

    /**
     * An inline service per transition or global action (see B-28).
     *
     * @param array{service: string, position: string} $customHtmlConfig
     */
    private function createCustomHtmlService(array $customHtmlConfig, int|string $actionOrTransitionName, bool $isGlobalAction): Definition
    {
        $definition = new Definition($customHtmlConfig['service'], [$actionOrTransitionName, $isGlobalAction, $customHtmlConfig['position']]);
        $definition->setPublic(false);
        $definition->setAutowired(true);

        return $definition;
    }

    /**
     * @param string[] $places
     */
    private function createMarkingStore(
        string $workflowName,
        array $places,
        array $workflowConfig,
        ?string $markingStoreType,
        ?string $markingStoreService
    ): ChildDefinition|Reference|null {
        if ($markingStoreType === null) {
            return $markingStoreService === null ? null : new Reference($markingStoreService);
        }

        $markingStoreDefinition = new ChildDefinition('workflow.marking_store.' . $markingStoreType);

        $typeArguments = match ($markingStoreType) {
            'state_table' => [$workflowName],
            'data_object_splitted_state' => [$workflowName, $places],
            'single_state' => [true],
            'multiple_state' => [false],
            default => [],
        };

        foreach ($typeArguments as $argument) {
            $markingStoreDefinition->addArgument($argument);
        }

        foreach ($workflowConfig['marking_store']['arguments'] ?? [] as $argument) {
            $markingStoreDefinition->addArgument($argument);
        }

        return $markingStoreDefinition;
    }

    private function registerSupportStrategy(ContainerBuilder $container, string $workflowId, array $workflowConfig): void
    {
        $registryDefinition = $container->getDefinition('workflow.registry');

        if ($workflowConfig['supports']) {
            foreach ((array) $workflowConfig['supports'] as $supportedClassName) {
                $strategyDefinition = new Definition(
                    Workflow\SupportStrategy\InstanceOfSupportStrategy::class,
                    [$supportedClassName]
                );
                $strategyDefinition->setPublic(false);
                $registryDefinition->addMethodCall('addWorkflow', [new Reference($workflowId), $strategyDefinition]);
            }

            return;
        }

        if (!isset($workflowConfig['support_strategy'])) {
            return;
        }

        $supportStrategyType = $workflowConfig['support_strategy']['type'] ?? null;
        if ($supportStrategyType !== null) {
            $supportStrategyDefinition = new ChildDefinition('workflow.support_strategy.' . $supportStrategyType);
            foreach ($workflowConfig['support_strategy']['arguments'] ?? [] as $argument) {
                $supportStrategyDefinition->addArgument($argument);
            }
            $registryDefinition->addMethodCall('addWorkflow', [new Reference($workflowId), $supportStrategyDefinition]);
        } elseif (isset($workflowConfig['support_strategy']['service'])) {
            $registryDefinition->addMethodCall(
                'addWorkflow',
                [new Reference($workflowId), new Reference($workflowConfig['support_strategy']['service'])]
            );
        }
    }

    private function registerAuditTrail(ContainerBuilder $container, string $workflowId, string $workflowName, array $workflowConfig): void
    {
        if (!$workflowConfig['audit_trail']['enabled']) {
            return;
        }

        $listener = new Definition(Workflow\EventListener\AuditTrailListener::class);
        $listener->setPublic(false);
        $listener->addTag('monolog.logger', ['channel' => 'workflow']);
        foreach (['leave' => 'onLeave', 'transition' => 'onTransition', 'enter' => 'onEnter'] as $event => $method) {
            $listener->addTag('kernel.event_listener', ['event' => sprintf('workflow.%s.%s', $workflowName, $event), 'method' => $method]);
        }
        $listener->addArgument(new Reference('logger'));
        $container->setDefinition(sprintf('.%s.listener.audit_trail', $workflowId), $listener);
    }

    private function registerGuard(ContainerBuilder $container, string $workflowId, string $workflowName, array $workflowConfig): void
    {
        $guard = new Definition(Workflow\EventListener\GuardListener::class);
        $guard->setPublic(false);
        $configuration = [];
        foreach ($workflowConfig['transitions'] as $transitionName => $config) {
            if (!isset($config['guard'])) {
                continue;
            }

            if (!class_exists(ExpressionLanguage::class)) {
                throw new LogicException(
                    'Cannot guard workflows as the ExpressionLanguage component is not installed.'
                );
            }

            if (!class_exists(AuthenticationEvents::class)) {
                throw new LogicException('Cannot guard workflows as the Security component is not installed.');
            }

            $eventName = sprintf('workflow.%s.guard.%s', $workflowName, $transitionName);
            $guard->addTag('kernel.event_listener', ['event' => $eventName, 'method' => 'onTransition']);
            $configuration[$eventName] = $config['guard'];
        }

        if (!$configuration) {
            return;
        }

        $guard->setArguments([
            $configuration,
            new Reference('workflow.security.expression_language'),
            new Reference('security.token_storage'),
            new Reference('security.authorization_checker'),
            new Reference('security.authentication.trust_resolver'),
            new Reference('security.role_hierarchy'),
            new Reference('validator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

        $container->setDefinition(sprintf('.%s.listener.guard', $workflowId), $guard);
        $container->setParameter('workflow.has_guard_listeners', true);
    }

    /**
     * Validates the places and transitions at compile time, like FrameworkBundle does with its definition validators.
     * The definition is built from the config with plain transitions, so no service needs to be instantiated.
     *
     * @param string[] $places
     *
     * @throws LogicException if the definition is invalid
     */
    private function validateDefinition(string $workflowName, string $type, array $places, array $workflowConfig): void
    {
        $transitions = [];
        foreach ($workflowConfig['transitions'] as $transitionName => $transitionConfig) {
            if ('state_machine' === $type) {
                foreach ($transitionConfig['from'] as $from) {
                    foreach ($transitionConfig['to'] as $to) {
                        $transitions[] = new Workflow\Transition((string) $transitionName, $from, $to);
                    }
                }
            } else {
                $transitions[] = new Workflow\Transition((string) $transitionName, $transitionConfig['from'], $transitionConfig['to']);
            }
        }

        try {
            $definition = new Workflow\Definition($places, $transitions, $workflowConfig['initial_markings'] ?? null);
        } catch (LogicException $e) {
            throw new LogicException(sprintf('The definition of the workflow "%s" is invalid: %s', $workflowName, $e->getMessage()), 0, $e);
        }

        ('state_machine' === $type ? new StateMachineValidator() : new WorkflowValidator())->validate($definition, $workflowName);
    }
}
