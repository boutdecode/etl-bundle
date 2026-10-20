<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Repository;

use BoutDeCode\ETLBundle\Entity\WorkflowExecutionStatistic;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\WorkflowExecutionStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\WorkflowExecutionStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowExecutionStatistic as WorkflowExecutionStatisticInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @extends ServiceEntityRepository<WorkflowExecutionStatistic>
 */
#[AsAlias(WorkflowExecutionStatisticProvider::class)]
#[AsAlias(WorkflowExecutionStatisticPersister::class)]
class WorkflowExecutionStatisticRepository extends ServiceEntityRepository implements WorkflowExecutionStatisticPersister, WorkflowExecutionStatisticProvider
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowExecutionStatistic::class);
    }

    public function create(WorkflowExecutionStatisticInterface $workflowExecutionStatistic): WorkflowExecutionStatisticInterface
    {
        $this->getEntityManager()->persist($workflowExecutionStatistic);
        $this->getEntityManager()->flush();

        return $workflowExecutionStatistic;
    }

    /**
     * @return array<int, WorkflowExecutionStatistic>
     */
    public function findByWorkflow(Workflow $workflow, int $limit = 100): iterable
    {
        return $this->findBy([
            'workflow' => $workflow,
        ], [
            'startedAt' => 'DESC',
        ], $limit);
    }

    /**
     * @return array<int, WorkflowExecutionStatistic>
     */
    public function findByWorkflowBetween(Workflow $workflow, \DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        $expr = Criteria::expr();

        return $this->matching(
            Criteria::create()
                ->where($expr->eq('workflow', $workflow))
                ->andWhere($expr->gte('startedAt', $from))
                ->andWhere($expr->lte('startedAt', $to))
                ->orderBy([
                    'startedAt' => Order::Descending,
                ]),
        )->toArray();
    }
}
