<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Workflow;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\WorkflowPersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow as WorkflowInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<Workflow>
 */
#[AsAlias(WorkflowProvider::class)]
#[AsAlias(WorkflowPersister::class)]
class WorkflowRepository extends ServiceEntityRepository implements WorkflowPersister, WorkflowProvider
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workflow::class);
    }

    public function create(WorkflowInterface $workflow): WorkflowInterface
    {
        return $this->save($workflow);
    }

    public function save(WorkflowInterface $workflow): WorkflowInterface
    {
        $this->getEntityManager()->persist($workflow);
        $this->getEntityManager()->flush();

        return $workflow;
    }

    public function findWorkflowByIdentifier(string $identifier): ?WorkflowInterface
    {
        return $this->find($identifier);
    }
}
