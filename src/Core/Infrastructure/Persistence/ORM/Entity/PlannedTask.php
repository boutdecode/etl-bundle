<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use BoutDeCode\ETLBundle\Core\Infrastructure\Api\Request\PlannedTaskPayload;
use BoutDeCode\ETLBundle\Core\Infrastructure\Api\State\PlannedTaskProcessor;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\PlannedTaskRepository;
use BoutDeCode\ETLBundle\Run\Infrastructure\Api\State\PlannedTaskExecuteProcessor;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractPlannedTask;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline as PipelineInterface;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow as WorkflowInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(input: PlannedTaskPayload::class, processor: PlannedTaskProcessor::class),
        new Put(input: PlannedTaskPayload::class, read: false, processor: PlannedTaskProcessor::class),
        new Post(
            uriTemplate: '/planned_tasks/{id}/execute',
            status: 202,
            openapi: new Operation(summary: 'Plans the next pipeline of the task now.'),
            input: false,
            read: false,
            deserialize: false,
            validate: false,
            name: 'boutdecode_etl_planned_task_execute',
            processor: PlannedTaskExecuteProcessor::class,
        ),
    ],
    formats: ['jsonld', 'json'],
    order: [
        'createdAt' => 'DESC',
    ],
)]
#[ORM\Entity(repositoryClass: PlannedTaskRepository::class)]
#[ORM\Table(name: 'etl_planned_task')]
class PlannedTask extends AbstractPlannedTask
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(length: 255)]
    protected string $name;

    #[ORM\Column]
    protected bool $enabled = true;

    #[ORM\ManyToOne(targetEntity: Workflow::class)]
    #[ORM\JoinColumn(name: 'workflow_id', nullable: false)]
    protected WorkflowInterface $workflow;

    // The pipeline can be purged independently, hence SET NULL.
    #[ORM\ManyToOne(targetEntity: Pipeline::class)]
    #[ORM\JoinColumn(name: 'pipeline_id', nullable: true, onDelete: 'SET NULL')]
    protected ?PipelineInterface $pipeline = null;

    #[ORM\Column(length: 255)]
    protected string $schedule;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    protected array $input = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    protected array $configuration = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $updatedAt = null;

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        string $name,
        WorkflowInterface $workflow,
        string $schedule,
        bool $enabled = true,
        array $input = [],
        array $configuration = [],
    ) {
        $this->id = (string) Uuid::v7();
        $this->name = $name;
        $this->workflow = $workflow;
        $this->schedule = $schedule;
        $this->enabled = $enabled;
        $this->input = $input;
        $this->configuration = $configuration;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $configuration
     */
    public function update(
        string $name,
        WorkflowInterface $workflow,
        string $schedule,
        bool $enabled,
        array $input,
        array $configuration,
    ): void {
        $this->name = $name;
        $this->workflow = $workflow;
        $this->schedule = $schedule;
        $this->enabled = $enabled;
        $this->input = $input;
        $this->configuration = $configuration;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
