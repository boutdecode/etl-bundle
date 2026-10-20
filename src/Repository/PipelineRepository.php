<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Repository;

use BoutDeCode\ETLBundle\Entity\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline as PipelineInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<Pipeline>
 */
#[AsAlias(PipelineProvider::class)]
#[AsAlias(PipelinePersister::class)]
class PipelineRepository extends ServiceEntityRepository implements PipelinePersister, PipelineProvider
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pipeline::class);
    }

    public function create(PipelineInterface $pipeline): PipelineInterface
    {
        return $this->save($pipeline);
    }

    public function save(PipelineInterface $pipeline): PipelineInterface
    {
        $this->getEntityManager()->persist($pipeline);
        $this->getEntityManager()->flush();

        return $pipeline;
    }

    public function delete(PipelineInterface $pipeline): void
    {
        $this->getEntityManager()->remove($pipeline);
        $this->getEntityManager()->flush();
    }

    public function findPipelineByIdentifier(string $identifier): ?PipelineInterface
    {
        return $this->find($identifier);
    }

    /**
     * Pipelines waiting to be picked up by the scheduler: pending, and either unplanned or due.
     *
     * @return array<int, Pipeline>
     */
    public function findScheduledPipelines(): array
    {
        $expr = Criteria::expr();

        return $this->matching(
            Criteria::create()
                ->where($expr->eq('status', PipelineStatus::PENDING))
                ->andWhere($expr->orX($expr->isNull('scheduledAt'), $expr->lte('scheduledAt', new \DateTimeImmutable()))),
        )->toArray();
    }

    /**
     * @return array<int, Pipeline>
     */
    public function findPurgeablePipelines(\DateTimeImmutable $before): array
    {
        $expr = Criteria::expr();

        return $this->matching(
            Criteria::create()
                ->where($expr->in('status', [PipelineStatus::COMPLETED, PipelineStatus::FAILED]))
                ->andWhere($expr->lt('finishedAt', $before)),
        )->toArray();
    }
}
