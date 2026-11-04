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


namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Workflow\Dumper\GraphvizDumper;
use Pimcore\Workflow\Dumper\StateMachineGraphvizDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Workflow\Dumper\DumperInterface;
use Symfony\Component\Workflow\Dumper\MermaidDumper;
use Symfony\Component\Workflow\Dumper\PlantUmlDumper;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Debug\TraceableWorkflow;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:workflow:dump',
    description: 'Dump a workflow',
    help: <<<'EOT'
        The <info>%command.name%</info> command dumps the graphical representation of a
        workflow in DOT (default), PlantUML or Mermaid format

            %command.full_name% <workflow name> | dot -Tpng > workflow.png
            %command.full_name% <workflow name> --dump-format=puml | java -jar plantuml.jar -p > workflow.png
        EOT
)]
class WorkflowDumpCommand extends AbstractCommand
{
    private const array DUMP_FORMATS = ['dot', 'puml', 'mermaid'];

    /**
     * @param ServiceProviderInterface<WorkflowInterface> $workflows the Pimcore workflows and state machines, by workflow name
     */
    public function __construct(
        #[AutowireLocator('pimcore.workflow', indexAttribute: 'name')]
        private readonly ServiceProviderInterface $workflows,
        private readonly GraphvizDumper $graphvizDumper,
        private readonly StateMachineGraphvizDumper $stateMachineGraphvizDumper,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'A workflow name', null, fn () => array_keys($this->workflows->getProvidedServices())),
                new InputArgument('marking', InputArgument::IS_ARRAY, 'A marking (a list of places)'),
                new InputOption('dump-format', null, InputOption::VALUE_REQUIRED, 'The dump format', 'dot', self::DUMP_FORMATS),
            ]);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        if (!$this->workflows->has($name)) {
            throw new InvalidArgumentException(sprintf('No workflow or state machine named "%s" found.', $name), self::INVALID);
        }

        $workflow = $this->workflows->get($name);
        $definition = $workflow->getDefinition();

        $marking = new Marking();
        $unknownPlaces = [];
        foreach ($input->getArgument('marking') as $place) {
            if (!in_array($place, $definition->getPlaces(), true)) {
                $unknownPlaces[] = $place;
            }
            $marking->mark($place);
        }

        if ($unknownPlaces) {
            throw new InvalidArgumentException(sprintf(
                'The workflow "%s" has no place(s) %s. Valid places: %s.',
                $name,
                implode(', ', $unknownPlaces),
                implode(', ', $definition->getPlaces())
            ), self::INVALID);
        }

        $output->writeln($this->getDumper($workflow, $input->getOption('dump-format'))->dump($definition, $marking, [
            'workflowName' => $workflow->getName(),
            'title' => $workflow->getName(),
        ]));

        return self::SUCCESS;
    }

    private function getDumper(WorkflowInterface $workflow, string $format): DumperInterface
    {
        // in debug mode the workflows are decorated
        if ($workflow instanceof TraceableWorkflow) {
            $workflow = $workflow->getInner();
        }
        $isStateMachine = $workflow instanceof StateMachine;

        return match ($format) {
            'dot' => $isStateMachine ? $this->stateMachineGraphvizDumper : $this->graphvizDumper,
            'puml' => new PlantUmlDumper($isStateMachine ? PlantUmlDumper::STATEMACHINE_TRANSITION : PlantUmlDumper::WORKFLOW_TRANSITION),
            'mermaid' => new MermaidDumper($isStateMachine ? MermaidDumper::TRANSITION_TYPE_STATEMACHINE : MermaidDumper::TRANSITION_TYPE_WORKFLOW),
            default => throw new InvalidArgumentException(sprintf(
                'Invalid --dump-format "%s", expected one of: %s.',
                $format,
                implode(', ', self::DUMP_FORMATS)
            ), self::INVALID),
        };
    }
}
