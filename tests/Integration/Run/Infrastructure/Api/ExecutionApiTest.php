<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration\Run\Infrastructure\Api;

use BoutDeCode\ETLBundle\Tests\Integration\ApiTestCase;

final class ExecutionApiTest extends ApiTestCase
{
    public function testExecutingAWorkflowRunsItsPipeline(): void
    {
        $workflow = $this->createUppercaseWorkflow();

        $launched = $this->request('POST', '/workflows/' . $workflow['id'] . '/execute', [
            'name' => 'Manual run',
            'input' => [
                'greeting' => 'hello',
            ],
        ], 202);
        self::assertSame('Manual run', $launched['name']);
        self::assertStringEndsWith('/workflows/' . $workflow['id'], $launched['workflow']);

        $pipeline = $this->request('GET', '/pipelines/' . $launched['id']);
        self::assertSame('completed', $pipeline['status'], 'The "async" transport is synchronous in the test kernel.');
        self::assertNotNull($pipeline['startedAt']);
        self::assertNotNull($pipeline['finishedAt']);
        self::assertSame('shout', $pipeline['steps'][0]['name']);
        self::assertArrayNotHasKey('runnableSteps', $pipeline);

        $histories = $this->members($this->request('GET', '/pipeline_histories?pipeline=' . $launched['id']));
        self::assertCount(1, $histories);
        self::assertSame('completed', $histories[0]['status']);
        self::assertSame('test.uppercase', $histories[0]['stepHistories'][0]['stepCode']);
        self::assertSame([
            'greeting' => 'HELLO',
        ], $histories[0]['stepHistories'][0]['result']);

        $statistics = $this->members($this->request('GET', '/workflow_statistics?workflow=' . $workflow['id']));
        self::assertCount(1, $statistics);
        self::assertSame(1, $statistics[0]['totalCount']);
        self::assertSame(1, $statistics[0]['successCount']);
        self::assertCount(1, $this->members($this->request('GET', '/workflow_execution_statistics?workflow=' . $workflow['id'])));

        $list = $this->request('GET', '/pipelines?status=completed&workflow=' . $workflow['id']);
        self::assertSame(1, $this->total($list));
        self::assertSame(0, $this->total($this->request('GET', '/pipelines?status=failed')));
    }

    public function testExecutingWithoutBodyIsAllowed(): void
    {
        $workflow = $this->createUppercaseWorkflow();

        $launched = $this->request('POST', '/workflows/' . $workflow['id'] . '/execute', null, 202);

        self::assertSame('Shout', $launched['name']);
    }

    public function testExecutingAnUnknownWorkflowIsNotFound(): void
    {
        $this->request('POST', '/workflows/00000000-0000-0000-0000-000000000000/execute', null, 404);
    }

    public function testDeletingAPipelinePurgesIt(): void
    {
        $workflow = $this->createUppercaseWorkflow();
        $launched = $this->request('POST', '/workflows/' . $workflow['id'] . '/execute', [
            'input' => [
                'a' => 'b',
            ],
        ], 202);

        $this->request('DELETE', '/pipelines/' . $launched['id'], null, 204);
        $this->request('GET', '/pipelines/' . $launched['id'], null, 404);
        self::assertSame([], $this->members($this->request('GET', '/pipeline_histories')));
    }
}
