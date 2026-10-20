<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration;

final class PlannedTaskApiTest extends ApiTestCase
{
    public function testInvalidCronExpressionIsRejected(): void
    {
        $workflow = $this->createUppercaseWorkflow();

        $problem = $this->request('POST', '/planned_tasks', [
            'name' => 'Nightly',
            'workflowId' => $workflow['id'],
            'schedule' => 'whenever',
        ], 422);

        self::assertSame('schedule', $problem['violations'][0]['propertyPath']);
    }

    public function testUnknownWorkflowIsRejected(): void
    {
        $this->request('POST', '/planned_tasks', [
            'name' => 'Nightly',
            'workflowId' => '00000000-0000-0000-0000-000000000000',
            'schedule' => '0 3 * * *',
        ], 422);
    }

    public function testPlannedTaskLifecycle(): void
    {
        $workflow = $this->createUppercaseWorkflow();

        $task = $this->request('POST', '/planned_tasks', [
            'name' => 'Nightly',
            'workflowId' => $workflow['id'],
            'schedule' => '0 3 * * *',
            'input' => [
                'greeting' => 'hi',
            ],
        ], 201);
        self::assertTrue($task['enabled']);
        self::assertArrayNotHasKey('pipeline', $task, 'Null values are omitted.');

        $executed = $this->request('POST', '/planned_tasks/' . $task['id'] . '/execute', null, 202);
        self::assertNotNull($executed['pipeline'], 'Running a task plans its next pipeline.');

        $pipeline = $this->request('GET', substr((string) $executed['pipeline'], strlen('/etl')));
        self::assertSame('pending', $pipeline['status']);
        self::assertNotNull($pipeline['scheduledAt']);
        self::assertSame([
            'greeting' => 'hi',
        ], $pipeline['input']);

        $updated = $this->request('PUT', '/planned_tasks/' . $task['id'], [
            'name' => 'Nightly',
            'workflowId' => $workflow['id'],
            'schedule' => '0 4 * * *',
            'enabled' => false,
        ]);
        self::assertFalse($updated['enabled']);
        self::assertSame('0 4 * * *', $updated['schedule']);

        self::assertSame(1, $this->total($this->request('GET', '/planned_tasks')));
    }
}
