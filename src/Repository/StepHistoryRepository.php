<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Repository;

use BoutDeCode\ETLBundle\Entity\StepHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\StepHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory as StepHistoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<StepHistory>
 */
#[AsAlias(StepHistoryPersister::class)]
class StepHistoryRepository extends ServiceEntityRepository implements StepHistoryPersister
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StepHistory::class);
    }

    public function create(StepHistoryInterface $stepHistory): StepHistoryInterface
    {
        $this->getEntityManager()->persist($stepHistory);
        $this->getEntityManager()->flush();

        return $stepHistory;
    }

    public function delete(StepHistoryInterface $stepHistory): void
    {
        $this->getEntityManager()->remove($stepHistory);
        $this->getEntityManager()->flush();
    }
}
