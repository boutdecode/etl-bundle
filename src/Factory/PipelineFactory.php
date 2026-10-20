<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Factory;

use BoutDeCode\ETLBundle\Entity\Pipeline;
use BoutDeCode\ETLBundle\Entity\Step;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory as PipelineFactoryInterface;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Webmozart\Assert\Assert;

/**
 * Builds (without persisting) pipelines out of workflows.
 */
#[AsAlias(PipelineFactoryInterface::class)]
final readonly class PipelineFactory implements PipelineFactoryInterface
{
    public function __construct(
        private WorkflowProvider $workflowProvider,
        private StepFactory $stepFactory,
    ) {
    }

    public function create(?string $name = null, array $steps = [], array $configuration = []): never
    {
        throw new \LogicException('A pipeline always belongs to a workflow: use createFromWorkflow() or createFromWorkflowId().');
    }

    public function createFromWorkflowId(
        string $workflowId,
        array $overrideConfiguration = [],
        array $input = [],
    ): Pipeline {
        $workflow = $this->workflowProvider->findWorkflowByIdentifier($workflowId);

        Assert::isInstanceOf($workflow, Workflow::class, sprintf('Workflow "%s" not found.', $workflowId));

        return $this->createFromWorkflow($workflow, null, $overrideConfiguration, $input);
    }

    public function createFromWorkflow(
        Workflow $workflow,
        ?string $name = null,
        array $overrideConfiguration = [],
        array $input = [],
    ): Pipeline {
        $steps = [];
        foreach (array_values($workflow->getStepConfiguration()) as $index => $stepConfiguration) {
            $steps[] = $this->createStep($stepConfiguration, $index);
        }

        return new Pipeline(
            $workflow,
            $name ?? $workflow->getName(),
            $steps,
            array_merge($workflow->getConfiguration(), $overrideConfiguration),
            $input,
        );
    }

    private function createStep(mixed $stepConfiguration, int $index): Step
    {
        Assert::isArray($stepConfiguration, sprintf('Step #%d of the workflow must be an object.', $index));
        Assert::string($stepConfiguration['code'] ?? null, sprintf('Step #%d of the workflow has no "code".', $index));

        $name = $stepConfiguration['name'] ?? null;
        $configuration = $stepConfiguration['configuration'] ?? [];
        $order = $stepConfiguration['order'] ?? $index;

        Assert::nullOrString($name);
        Assert::isMap($configuration);
        Assert::integer($order);

        return $this->stepFactory->create($stepConfiguration['code'], $name, $configuration, $order);
    }
}
