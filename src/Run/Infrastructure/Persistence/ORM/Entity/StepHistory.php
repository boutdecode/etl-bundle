<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Entity;

use BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Repository\StepHistoryRepository;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\AbstractStepHistory;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StepHistoryRepository::class)]
#[ORM\Table(name: 'etl_step_history')]
class StepHistory extends AbstractStepHistory
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: PipelineHistory::class, inversedBy: 'stepHistories')]
    #[ORM\JoinColumn(name: 'pipeline_history_id', nullable: true, onDelete: 'CASCADE')]
    private ?PipelineHistory $pipelineHistory = null;

    #[Groups(['pipeline_history:read'])]
    // The pipeline steps may be edited or purged, so the identity of the step is denormalized.
    #[ORM\Column(length: 255)]
    private string $stepCode;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(length: 255)]
    private string $stepName;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(length: 20, enumType: StepHistoryStatusEnum::class)]
    protected StepHistoryStatusEnum $status;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected mixed $input = null;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected mixed $result = null;

    public function __construct(
        string $stepCode,
        string $stepName,
        StepHistoryStatusEnum $status,
        mixed $input = null,
        mixed $result = null,
    ) {
        $this->id = (string) Uuid::v7();
        $this->stepCode = $stepCode;
        $this->stepName = $stepName;
        $this->status = $status;
        $this->input = $input;
        $this->result = $result;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStepCode(): string
    {
        return $this->stepCode;
    }

    public function getStepName(): string
    {
        return $this->stepName;
    }

    public function getPipelineHistory(): ?PipelineHistory
    {
        return $this->pipelineHistory;
    }

    public function setPipelineHistory(PipelineHistory $pipelineHistory): void
    {
        $this->pipelineHistory = $pipelineHistory;
    }
}
