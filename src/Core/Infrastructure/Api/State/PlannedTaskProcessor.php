<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use BoutDeCode\ETLBundle\Core\Infrastructure\Api\Request\PlannedTaskPayload;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\PlannedTask;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Workflow;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\PlannedTaskRepository;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\WorkflowRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<PlannedTaskPayload, PlannedTask>
 */
final readonly class PlannedTaskProcessor implements ProcessorInterface
{
    public function __construct(
        private PlannedTaskRepository $plannedTasks,
        private WorkflowRepository $workflows,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): PlannedTask {
        $workflow = $this->workflow($data->workflowId);

        if ($operation instanceof Put) {
            $plannedTask = $this->plannedTasks->find($uriVariables['id']) ??
                throw new NotFoundHttpException('Planned task not found.');

            $plannedTask->update($data->name, $workflow, $data->schedule, $data->enabled, $data->input, $data->configuration);
            $this->plannedTasks->save($plannedTask);

            return $plannedTask;
        }

        $plannedTask = new PlannedTask(
            $data->name,
            $workflow,
            $data->schedule,
            $data->enabled,
            $data->input,
            $data->configuration
        );
        $this->plannedTasks->create($plannedTask);

        return $plannedTask;
    }

    private function workflow(string $id): Workflow
    {
        return $this->workflows->find($id) ??
            throw new UnprocessableEntityHttpException(sprintf('Workflow "%s" not found.', $id));
    }
}
