<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\PlannedTask;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PlannedTaskPersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PlannedTaskProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask as PlannedTaskInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<PlannedTask>
 */
#[AsAlias(PlannedTaskProvider::class)]
#[AsAlias(PlannedTaskPersister::class)]
class PlannedTaskRepository extends ServiceEntityRepository implements PlannedTaskPersister, PlannedTaskProvider
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlannedTask::class);
    }

    public function create(PlannedTaskInterface $plannedTask): PlannedTaskInterface
    {
        return $this->save($plannedTask);
    }

    public function save(PlannedTaskInterface $plannedTask): PlannedTaskInterface
    {
        $this->getEntityManager()->persist($plannedTask);
        $this->getEntityManager()->flush();

        return $plannedTask;
    }

    public function findByIdentifier(string $identifier): ?PlannedTaskInterface
    {
        return $this->find($identifier);
    }

    /**
     * Every enabled task is a candidate; the core decides whether it is executable
     * (IsPlannedTaskExecutableSpecification) depending on its current pipeline.
     *
     * @return array<int, PlannedTask>
     */
    public function findScheduled(): array
    {
        return $this->findBy([
            'enabled' => true,
        ]);
    }
}
