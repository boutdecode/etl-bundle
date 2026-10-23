<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use BoutDeCode\ETLBundle\Core\Infrastructure\Api\Request\WorkflowPayload;
use BoutDeCode\ETLBundle\Core\Infrastructure\Api\State\WorkflowProcessor;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\WorkflowRepository;
use BoutDeCode\ETLBundle\Run\Infrastructure\Api\State\WorkflowExecuteProcessor;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractWorkflow;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(input: WorkflowPayload::class, processor: WorkflowProcessor::class),
        new Put(input: WorkflowPayload::class, read: false, processor: WorkflowProcessor::class),
        new Post(
            uriTemplate: '/workflows/{id}/execute',
            status: 202,
            openapi: new Operation(summary: 'Creates a pipeline from the workflow and queues its execution.'),
            normalizationContext: [
                'groups' => ['pipeline:read'],
            ],
            input: false,
            output: Pipeline::class,
            read: false,
            deserialize: false,
            validate: false,
            name: 'boutdecode_etl_workflow_execute',
            processor: WorkflowExecuteProcessor::class,
        ),
    ],
    formats: ['jsonld', 'json'],
    order: [
        'createdAt' => 'DESC',
    ],
)]
#[ORM\Entity(repositoryClass: WorkflowRepository::class)]
#[ORM\Table(name: 'etl_workflow')]
class Workflow extends AbstractWorkflow
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(length: 255)]
    protected string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    protected array $stepConfiguration = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    protected array $configuration = [];

    #[ORM\Column]
    protected bool $notifyOnSuccess = false;

    #[ORM\Column]
    protected bool $notifyOnFailure = false;

    /**
     * @var string[]|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $notificationProviders = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $updatedAt = null;

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $stepConfiguration
     * @param string[]|null $notificationProviders
     */
    public function __construct(
        string $name,
        array $configuration = [],
        array $stepConfiguration = [],
        ?string $description = null,
        bool $notifyOnSuccess = false,
        bool $notifyOnFailure = false,
        ?array $notificationProviders = null,
    ) {
        $this->id = (string) Uuid::v7();
        $this->name = $name;
        $this->configuration = $configuration;
        $this->stepConfiguration = $stepConfiguration;
        $this->description = $description;
        $this->notifyOnSuccess = $notifyOnSuccess;
        $this->notifyOnFailure = $notifyOnFailure;
        $this->notificationProviders = $notificationProviders;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $stepConfiguration
     * @param string[]|null $notificationProviders
     */
    public function update(
        string $name,
        array $configuration,
        array $stepConfiguration,
        ?string $description,
        bool $notifyOnSuccess,
        bool $notifyOnFailure,
        ?array $notificationProviders,
    ): void {
        $this->name = $name;
        $this->configuration = $configuration;
        $this->stepConfiguration = $stepConfiguration;
        $this->description = $description;
        $this->notifyOnSuccess = $notifyOnSuccess;
        $this->notifyOnFailure = $notifyOnFailure;
        $this->notificationProviders = $notificationProviders;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
