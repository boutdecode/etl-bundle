<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use BoutDeCode\ETLBundle\Entity\PlannedTask;
use BoutDeCode\ETLBundle\Repository\PlannedTaskRepository;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecutePlannedTaskCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Plans the next pipeline of a task now. The core ignores the request when the task
 * is disabled or when its current pipeline has not started yet.
 *
 * @implements ProcessorInterface<null, PlannedTask>
 */
final readonly class PlannedTaskExecuteProcessor implements ProcessorInterface
{
    public function __construct(
        private PlannedTaskRepository $plannedTasks,
        private EntityManagerInterface $entityManager,
        private CommandBus $commandBus,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): PlannedTask
    {
        $plannedTask = $this->plannedTasks->find($uriVariables['id']) ??
            throw new NotFoundHttpException('Planned task not found.');

        $this->commandBus->dispatch(new ExecutePlannedTaskCommand($plannedTask->getId()));
        $this->entityManager->refresh($plannedTask);

        return $plannedTask;
    }
}
