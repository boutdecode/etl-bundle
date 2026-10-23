<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Step;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\StepPersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step as StepInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<Step>
 */
#[AsAlias(StepPersister::class)]
class StepRepository extends ServiceEntityRepository implements StepPersister
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Step::class);
    }

    public function create(StepInterface $step): StepInterface
    {
        $this->getEntityManager()->persist($step);
        $this->getEntityManager()->flush();

        return $step;
    }
}
