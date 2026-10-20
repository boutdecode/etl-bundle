<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use BoutDeCode\ETLBundle\Api\State\PipelineDeleteProcessor;
use BoutDeCode\ETLBundle\Repository\PipelineRepository;
use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractPipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow as WorkflowInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Delete(processor: PipelineDeleteProcessor::class),
    ],
    formats: ['jsonld', 'json'],
    normalizationContext: [
        'groups' => ['pipeline:read'],
    ],
    order: [
        'createdAt' => 'DESC',
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'status' => 'exact',
    'workflow' => 'exact',
])]
#[ORM\Entity(repositoryClass: PipelineRepository::class)]
#[ORM\Table(name: 'etl_pipeline')]
#[ORM\Index(name: 'etl_pipeline_status_idx', columns: ['status'])]
class Pipeline extends AbstractPipeline
{
    #[Groups(['pipeline:read'])]
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[Groups(['pipeline:read'])]
    #[ORM\ManyToOne(targetEntity: Workflow::class)]
    #[ORM\JoinColumn(name: 'workflow_id', nullable: false)]
    protected WorkflowInterface $workflow;

    #[Groups(['pipeline:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * @var Collection<int, Step>
     */
    #[ORM\OneToMany(targetEntity: Step::class, mappedBy: 'pipeline', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy([
        'order' => 'ASC',
    ])]
    #[Groups(['pipeline:read'])]
    protected iterable $steps;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['pipeline:read'])]
    #[ORM\Column(type: Types::JSON)]
    protected array $configuration = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['pipeline:read'])]
    #[ORM\Column(type: Types::JSON)]
    protected array $input = [];

    #[Groups(['pipeline:read'])]
    #[ORM\Column(length: 20, enumType: PipelineStatus::class)]
    protected PipelineStatus $status = PipelineStatus::PENDING;

    #[Groups(['pipeline:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[Groups(['pipeline:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $scheduledAt = null;

    #[Groups(['pipeline:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $startedAt = null;

    #[Groups(['pipeline:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $finishedAt = null;

    /**
     * @param iterable<Step> $steps
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $input
     */
    public function __construct(
        WorkflowInterface $workflow,
        ?string $name = null,
        iterable $steps = [],
        array $configuration = [],
        array $input = [],
    ) {
        $this->id = (string) Uuid::v7();
        $this->workflow = $workflow;
        $this->name = $name;
        $this->configuration = $configuration;
        $this->input = $input;
        $this->createdAt = new \DateTimeImmutable();
        $this->steps = new ArrayCollection();

        foreach ($steps as $step) {
            $this->addStep($step);
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addStep(Step $step): void
    {
        $step->setPipeline($this);
        $this->steps->add($step);
    }

    /**
     * Accessors used by the Symfony Workflow marking store (`pipeline_lifecycle`),
     * which works with the raw string value of the status.
     */
    public function getStatusValue(): string
    {
        return $this->status->value;
    }

    public function setStatusValue(string $status): void
    {
        $this->status = PipelineStatus::from($status);
    }
}
