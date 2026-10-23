<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Factory;

use BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Entity\PipelineHistory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\PipelineHistoryFactory as PipelineHistoryFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PipelineHistoryFactoryInterface::class)]
final class PipelineHistoryFactory implements PipelineHistoryFactoryInterface
{
    public function create(
        Pipeline $pipeline,
        PipelineHistoryStatusEnum $status,
        array $stepHistories,
        mixed $input,
        mixed $result,
    ): PipelineHistory {
        return new PipelineHistory($pipeline, $status, $stepHistories, $input, $result);
    }
}
