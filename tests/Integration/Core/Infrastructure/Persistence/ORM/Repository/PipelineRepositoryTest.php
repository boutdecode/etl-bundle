<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration\Core\Infrastructure\Persistence\ORM\Repository;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Pipeline;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Workflow;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\PipelineRepository;
use BoutDeCode\ETLBundle\Tests\Integration\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class PipelineRepositoryTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;

    private PipelineRepository $repository;

    private Workflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $repository = $this->entityManager->getRepository(Pipeline::class);
        self::assertInstanceOf(PipelineRepository::class, $repository);
        $this->repository = $repository;

        $this->workflow = new Workflow('Repo test');
        $this->entityManager->persist($this->workflow);
        $this->entityManager->flush();
    }

    public function testOnlyDuePendingPipelinesAreScheduled(): void
    {
        $unplanned = $this->pipeline();
        $due = $this->pipeline();
        $due->plan(new \DateTimeImmutable('-1 hour'));
        $future = $this->pipeline();
        $future->plan(new \DateTimeImmutable('+1 hour'));
        $running = $this->pipeline();
        $running->setStatusValue('in_progress');
        $this->entityManager->flush();

        $ids = array_map(static fn (Pipeline $pipeline): string => $pipeline->getId(), $this->repository->findScheduledPipelines());
        sort($ids);
        $expected = [$unplanned->getId(), $due->getId()];
        sort($expected);

        self::assertSame($expected, $ids);
    }

    public function testOnlyOldFinishedPipelinesArePurgeable(): void
    {
        $old = $this->finishedPipeline('completed', '-40 days');
        $oldFailed = $this->finishedPipeline('failed', '-35 days');
        $this->finishedPipeline('completed', '-1 day');
        $this->pipeline();
        $this->entityManager->flush();

        $ids = array_map(
            static fn (Pipeline $pipeline): string => $pipeline->getId(),
            $this->repository->findPurgeablePipelines(new \DateTimeImmutable('-30 days')),
        );
        sort($ids);
        $expected = [$old->getId(), $oldFailed->getId()];
        sort($expected);

        self::assertSame($expected, $ids);
    }

    private function pipeline(): Pipeline
    {
        $pipeline = new Pipeline($this->workflow, 'test');
        $this->entityManager->persist($pipeline);

        return $pipeline;
    }

    private function finishedPipeline(string $status, string $finishedAt): Pipeline
    {
        $pipeline = $this->pipeline();
        $pipeline->setStatusValue($status);
        (new \ReflectionProperty($pipeline, 'finishedAt'))->setValue($pipeline, new \DateTimeImmutable($finishedAt));

        return $pipeline;
    }
}
