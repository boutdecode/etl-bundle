<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Repository;

use BoutDeCode\ETLBundle\Run\Infrastructure\Persistence\ORM\Entity\PipelineHistory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\PipelineHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Provider\PipelineHistoryProvider;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory as PipelineHistoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<PipelineHistory>
 */
#[AsAlias(PipelineHistoryProvider::class)]
#[AsAlias(PipelineHistoryPersister::class)]
class PipelineHistoryRepository extends ServiceEntityRepository implements PipelineHistoryPersister, PipelineHistoryProvider
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PipelineHistory::class);
    }

    public function create(PipelineHistoryInterface $pipelineHistory): PipelineHistoryInterface
    {
        $this->getEntityManager()->persist($pipelineHistory);
        $this->getEntityManager()->flush();

        return $pipelineHistory;
    }

    public function delete(PipelineHistoryInterface $pipelineHistory): void
    {
        $this->getEntityManager()->remove($pipelineHistory);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<int, PipelineHistory>
     */
    public function findByPipeline(Pipeline $pipeline, int $limit = 100): iterable
    {
        return $this->findBy([
            'pipeline' => $pipeline,
        ], [
            'createdAt' => 'DESC',
        ], $limit);
    }

    /**
     * @return array<int, PipelineHistory>
     */
    public function findByPipelineBetween(Pipeline $pipeline, \DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        $expr = Criteria::expr();

        return $this->matching(
            Criteria::create()
                ->where($expr->eq('pipeline', $pipeline))
                ->andWhere($expr->gte('createdAt', $from))
                ->andWhere($expr->lte('createdAt', $to))
                ->orderBy([
                    'createdAt' => Order::Descending,
                ]),
        )->toArray();
    }
}
