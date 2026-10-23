<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Unit\Core\Infrastructure\Persistence\ORM\Factory;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Workflow;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Factory\PipelineFactory;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Factory\StepFactory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use PHPUnit\Framework\TestCase;

final class PipelineFactoryTest extends TestCase
{
    public function testStepsAndConfigurationAreCopiedFromTheWorkflow(): void
    {
        $workflow = new Workflow(
            'Import',
            [
                'retries' => 1,
                'timeout' => 10,
            ],
            [
                [
                    'code' => 'etl.extractor.csv_file',
                    'name' => 'read',
                    'configuration' => [
                        'path' => 'a.csv',
                    ],
                ],
                [
                    'code' => 'etl.loader.json_file',
                ],
            ],
        );

        $pipeline = $this->factory()->createFromWorkflow($workflow, null, [
            'timeout' => 99,
        ], [
            'source' => 'manual',
        ]);

        self::assertSame('Import', $pipeline->getName());
        self::assertSame(PipelineStatus::PENDING, $pipeline->getStatus());
        self::assertSame([
            'retries' => 1,
            'timeout' => 99,
        ], $pipeline->getConfiguration());
        self::assertSame([
            'source' => 'manual',
        ], $pipeline->getInput());

        $steps = [...$pipeline->getSteps()];
        self::assertCount(2, $steps);
        self::assertSame('read', $steps[0]->getName());
        self::assertSame([
            'path' => 'a.csv',
        ], $steps[0]->getConfiguration());
        self::assertSame(0, $steps[0]->getOrder());
        self::assertSame('etl.loader.json_file', $steps[1]->getName(), 'The name defaults to the code.');
        self::assertSame(1, $steps[1]->getOrder());
    }

    public function testStepWithoutCodeIsRejected(): void
    {
        $workflow = new Workflow('Broken', [], [[
            'name' => 'no code',
        ]]);

        $this->expectException(\InvalidArgumentException::class);

        $this->factory()->createFromWorkflow($workflow);
    }

    public function testUnknownWorkflowIdIsRejected(): void
    {
        $provider = $this->createStub(WorkflowProvider::class);
        $provider->method('findWorkflowByIdentifier')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);

        (new PipelineFactory($provider, new StepFactory()))->createFromWorkflowId('missing');
    }

    private function factory(): PipelineFactory
    {
        return new PipelineFactory($this->createStub(WorkflowProvider::class), new StepFactory());
    }
}
