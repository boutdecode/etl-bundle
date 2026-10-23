<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\StepRepository;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractStep;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StepRepository::class)]
#[ORM\Table(name: 'etl_step')]
class Step extends AbstractStep
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Pipeline::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(name: 'pipeline_id', nullable: false, onDelete: 'CASCADE')]
    private Pipeline $pipeline;

    #[Groups(['pipeline:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $name = null;

    #[Groups(['pipeline:read'])]
    #[ORM\Column(length: 255)]
    protected string $code;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['pipeline:read'])]
    #[ORM\Column(type: Types::JSON)]
    protected array $configuration = [];

    #[Groups(['pipeline:read'])]
    #[ORM\Column(name: 'sort_order')]
    protected int $order = 0;

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(string $code, ?string $name = null, array $configuration = [], int $order = 0)
    {
        $this->id = (string) Uuid::v7();
        $this->code = $code;
        $this->name = $name;
        $this->configuration = $configuration;
        $this->order = $order;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }

    public function setPipeline(Pipeline $pipeline): void
    {
        $this->pipeline = $pipeline;
    }
}
