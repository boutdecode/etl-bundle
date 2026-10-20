<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use BoutDeCode\ETLBundle\Repository\WorkflowStatisticRepository;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow as WorkflowInterface;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\AbstractWorkflowStatistic;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [new GetCollection(), new Get()],
    formats: ['jsonld', 'json'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'workflow' => 'exact',
])]
#[ORM\Entity(repositoryClass: WorkflowStatisticRepository::class)]
#[ORM\Table(name: 'etl_workflow_statistic')]
class WorkflowStatistic extends AbstractWorkflowStatistic
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\OneToOne(targetEntity: Workflow::class)]
    #[ORM\JoinColumn(name: 'workflow_id', nullable: false, unique: true, onDelete: 'CASCADE')]
    protected WorkflowInterface $workflow;

    #[ORM\Column]
    protected int $totalCount = 0;

    #[ORM\Column]
    protected int $successCount = 0;

    #[ORM\Column]
    protected int $failureCount = 0;

    #[ORM\Column(type: Types::BIGINT)]
    protected int $totalDurationMs = 0;

    #[ORM\Column(nullable: true)]
    protected ?int $minDurationMs = null;

    #[ORM\Column(nullable: true)]
    protected ?int $maxDurationMs = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $lastRunAt = null;

    #[ORM\Column(length: 20, nullable: true, enumType: PipelineHistoryStatusEnum::class)]
    protected ?PipelineHistoryStatusEnum $lastRunStatus = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $updatedAt;

    public function __construct(WorkflowInterface $workflow)
    {
        $this->id = (string) Uuid::v7();
        $this->workflow = $workflow;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
