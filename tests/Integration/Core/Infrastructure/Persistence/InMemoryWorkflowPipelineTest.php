<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration\Core\Infrastructure\Persistence;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Factory\PipelineFactory;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\PipelineRepository;
use BoutDeCode\ETLBundle\Tests\Integration\ApiTestCase;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\InMemoryWorkflow;
use Doctrine\ORM\EntityManagerInterface;

final class InMemoryWorkflowPipelineTest extends ApiTestCase
{
    public function testPipelineOfAnInMemoryWorkflowIsPersistedWithoutWorkflow(): void
    {
        $workflow = InMemoryWorkflow::fromArray([
            'name' => 'From file',
            'step_configuration' => [],
        ]);

        $pipeline = static::getContainer()->get(PipelineFactory::class)->createFromWorkflow($workflow);
        static::getContainer()->get(PipelineRepository::class)->create($pipeline);

        self::assertSame($workflow, $pipeline->getWorkflow());

        static::getContainer()->get(EntityManagerInterface::class)->clear();

        $reloaded = $this->request('GET', '/pipelines/' . $pipeline->getId());
        self::assertArrayNotHasKey('workflow', $reloaded);
        self::assertSame('From file', $reloaded['name']);
    }
}
