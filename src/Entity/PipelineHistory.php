<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use BoutDeCode\ETLBundle\Repository\PipelineHistoryRepository;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline as PipelineInterface;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\AbstractPipelineHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory as StepHistoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[ApiResource(
    operations: [new GetCollection(), new Get()],
    formats: ['jsonld', 'json'],
    normalizationContext: [
        'groups' => ['pipeline_history:read'],
    ],
    order: [
        'createdAt' => 'DESC',
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'pipeline' => 'exact',
    'status' => 'exact',
])]
#[ORM\Entity(repositoryClass: PipelineHistoryRepository::class)]
#[ORM\Table(name: 'etl_pipeline_history')]
class PipelineHistory extends AbstractPipelineHistory
{
    #[Groups(['pipeline_history:read'])]
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[Groups(['pipeline_history:read'])]
    #[ORM\ManyToOne(targetEntity: Pipeline::class)]
    #[ORM\JoinColumn(name: 'pipeline_id', nullable: false, onDelete: 'CASCADE')]
    protected PipelineInterface $pipeline;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(length: 20, enumType: PipelineHistoryStatusEnum::class)]
    protected PipelineHistoryStatusEnum $status;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected mixed $input = null;

    #[Groups(['pipeline_history:read'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected mixed $result = null;

    /**
     * @var Collection<int, StepHistory>
     */
    #[Groups(['pipeline_history:read'])]
    #[ORM\OneToMany(targetEntity: StepHistory::class, mappedBy: 'pipelineHistory', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected iterable $stepHistories;

    /**
     * @param iterable<StepHistoryInterface> $stepHistories
     */
    public function __construct(
        PipelineInterface $pipeline,
        PipelineHistoryStatusEnum $status,
        iterable $stepHistories = [],
        mixed $input = null,
        mixed $result = null,
    ) {
        $this->id = (string) Uuid::v7();
        $this->pipeline = $pipeline;
        $this->status = $status;
        $this->input = $input;
        $this->result = $result;
        $this->createdAt = new \DateTimeImmutable();
        $this->stepHistories = new ArrayCollection();

        foreach ($stepHistories as $stepHistory) {
            Assert::isInstanceOf($stepHistory, StepHistory::class);
            $stepHistory->setPipelineHistory($this);
            $this->stepHistories->add($stepHistory);
        }
    }

    public function getId(): string
    {
        return $this->id;
    }
}
