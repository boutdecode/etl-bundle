<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Statistics\Infrastructure\Persistence\ORM\Repository;

use BoutDeCode\ETLBundle\Statistics\Infrastructure\Persistence\ORM\Entity\WorkflowStatistic;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\WorkflowStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\WorkflowStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic as WorkflowStatisticInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<WorkflowStatistic>
 */
#[AsAlias(WorkflowStatisticProvider::class)]
#[AsAlias(WorkflowStatisticPersister::class)]
class WorkflowStatisticRepository extends ServiceEntityRepository implements WorkflowStatisticPersister, WorkflowStatisticProvider
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowStatistic::class);
    }

    public function create(WorkflowStatisticInterface $workflowStatistic): WorkflowStatisticInterface
    {
        return $this->save($workflowStatistic);
    }

    public function save(WorkflowStatisticInterface $workflowStatistic): WorkflowStatisticInterface
    {
        $this->getEntityManager()->persist($workflowStatistic);
        $this->getEntityManager()->flush();

        return $workflowStatistic;
    }

    public function findByWorkflow(Workflow $workflow): ?WorkflowStatisticInterface
    {
        return $this->findOneBy([
            'workflow' => $workflow,
        ]);
    }

    /**
     * @return array<int, WorkflowStatistic>
     */
    public function findAll(): array
    {
        return parent::findAll();
    }
}
