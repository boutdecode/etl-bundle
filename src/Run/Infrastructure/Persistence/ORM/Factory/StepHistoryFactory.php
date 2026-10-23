<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Factory;

use BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Entity\StepHistory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\StepHistoryFactory as StepHistoryFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(StepHistoryFactoryInterface::class)]
final class StepHistoryFactory implements StepHistoryFactoryInterface
{
    public function create(Step $step, StepHistoryStatusEnum $status, mixed $input, mixed $result): StepHistory
    {
        return new StepHistory($step->getCode(), $step->getName() ?? $step->getCode(), $status, $input, $result);
    }
}
