<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Factory;

use BoutDeCode\ETLBundle\Entity\WorkflowExecutionStatistic;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\WorkflowExecutionStatisticFactory as WorkflowExecutionStatisticFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowExecutionStatisticFactoryInterface::class)]
final class WorkflowExecutionStatisticFactory implements WorkflowExecutionStatisticFactoryInterface
{
    public function create(
        Workflow $workflow,
        PipelineHistoryStatusEnum $status,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $finishedAt,
    ): WorkflowExecutionStatistic {
        return new WorkflowExecutionStatistic($workflow, $status, $startedAt, $finishedAt);
    }
}
