<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Factory;

use BoutDeCode\ETLBundle\Entity\WorkflowStatistic;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\WorkflowStatisticFactory as WorkflowStatisticFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowStatisticFactoryInterface::class)]
final class WorkflowStatisticFactory implements WorkflowStatisticFactoryInterface
{
    public function create(Workflow $workflow): WorkflowStatistic
    {
        return new WorkflowStatistic($workflow);
    }
}
