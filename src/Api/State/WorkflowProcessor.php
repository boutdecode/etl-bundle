<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use BoutDeCode\ETLBundle\Api\Request\WorkflowPayload;
use BoutDeCode\ETLBundle\Entity\Workflow;
use BoutDeCode\ETLBundle\Factory\WorkflowFactory;
use BoutDeCode\ETLBundle\Repository\WorkflowRepository;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver\StepResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webmozart\Assert\Assert;

/**
 * Creates or replaces a workflow from a validated {@see WorkflowPayload}.
 *
 * @implements ProcessorInterface<WorkflowPayload, Workflow>
 */
final readonly class WorkflowProcessor implements ProcessorInterface
{
    public function __construct(
        private WorkflowRepository $workflows,
        private WorkflowFactory $workflowFactory,
        private StepResolver $stepResolver,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Workflow
    {
        $this->assertKnownSteps($data);

        if ($operation instanceof Put) {
            $workflow = $this->workflows->find($uriVariables['id']) ??
                throw new NotFoundHttpException('Workflow not found.');

            $workflow->update(
                $data->name,
                $data->configuration,
                $data->stepConfiguration,
                $data->description,
                $data->notifyOnSuccess,
                $data->notifyOnFailure,
                $data->notificationProviders,
            );
            $this->workflows->save($workflow);

            return $workflow;
        }

        $workflow = $this->workflowFactory->create(
            $data->name,
            $data->configuration,
            $data->stepConfiguration,
            $data->description,
            $data->notifyOnSuccess,
            $data->notifyOnFailure,
            $data->notificationProviders,
        );
        $this->workflows->create($workflow);

        return $workflow;
    }

    private function assertKnownSteps(WorkflowPayload $payload): void
    {
        foreach ($payload->stepConfiguration as $index => $step) {
            // The shape is guaranteed by the payload constraints.
            Assert::isArray($step);
            Assert::string($step['code'] ?? null);

            if ($this->stepResolver->resolve($step['code']) === null) {
                throw new UnprocessableEntityHttpException(sprintf('Unknown step code "%s" at stepConfiguration[%s].', $step['code'], $index));
            }
        }
    }
}
