<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Run\Application;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommand;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;

/**
 * Creates a pipeline out of a workflow and hands it over for immediate execution.
 *
 * The pipeline is moved to SCHEDULED before being persisted so the periodic scheduler
 * (which only picks up PENDING pipelines) cannot start it a second time.
 */
final readonly class PipelineLauncher
{
    public function __construct(
        private PipelineFactory $pipelineFactory,
        private PipelinePersister $pipelinePersister,
        private PipelineWorkflow $pipelineWorkflow,
        private CommandBus $commandBus,
    ) {
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $input
     */
    public function launch(Workflow $workflow, ?string $name = null, array $configuration = [], array $input = []): Pipeline
    {
        $pipeline = $this->pipelineFactory->createFromWorkflow($workflow, $name, $configuration, $input);

        $this->pipelineWorkflow->schedule($pipeline);
        $this->pipelinePersister->create($pipeline);

        $this->commandBus->dispatch(new ExecuteWorkflowCommand($pipeline->getId()));

        return $pipeline;
    }
}
