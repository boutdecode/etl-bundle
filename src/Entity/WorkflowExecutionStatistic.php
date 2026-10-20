<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use BoutDeCode\ETLBundle\Repository\WorkflowExecutionStatisticRepository;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow as WorkflowInterface;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\AbstractWorkflowExecutionStatistic;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [new GetCollection(), new Get()],
    formats: ['jsonld', 'json'],
    order: [
        'startedAt' => 'DESC',
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'workflow' => 'exact',
    'status' => 'exact',
])]
#[ORM\Entity(repositoryClass: WorkflowExecutionStatisticRepository::class)]
#[ORM\Table(name: 'etl_workflow_execution_statistic')]
class WorkflowExecutionStatistic extends AbstractWorkflowExecutionStatistic
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Workflow::class)]
    #[ORM\JoinColumn(name: 'workflow_id', nullable: false, onDelete: 'CASCADE')]
    protected WorkflowInterface $workflow;

    #[ORM\Column(length: 20, enumType: PipelineHistoryStatusEnum::class)]
    protected PipelineHistoryStatusEnum $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $finishedAt;

    public function __construct(
        WorkflowInterface $workflow,
        PipelineHistoryStatusEnum $status,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $finishedAt,
    ) {
        $this->id = (string) Uuid::v7();
        $this->workflow = $workflow;
        $this->status = $status;
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
