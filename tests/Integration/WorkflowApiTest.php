<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration;

final class WorkflowApiTest extends ApiTestCase
{
    public function testWorkflowLifecycle(): void
    {
        $created = $this->createUppercaseWorkflow();

        self::assertSame('Shout', $created['name']);
        self::assertSame([[
            'code' => 'test.uppercase',
            'name' => 'shout',
        ]], $created['stepConfiguration']);
        self::assertArrayNotHasKey('updatedAt', $created, 'Null values are omitted.');

        $shown = $this->request('GET', '/workflows/' . $created['id']);
        self::assertSame($created['id'], $shown['id']);

        $updated = $this->request('PUT', '/workflows/' . $created['id'], [
            'name' => 'Shout loudly',
            'stepConfiguration' => [[
                'code' => 'test.uppercase',
            ]],
            'notifyOnFailure' => true,
        ]);
        self::assertSame('Shout loudly', $updated['name']);
        self::assertTrue($updated['notifyOnFailure']);
        self::assertNotNull($updated['updatedAt']);

        $list = $this->request('GET', '/workflows');
        self::assertSame(1, $this->total($list));
        self::assertSame('Shout loudly', $this->members($list)[0]['name']);
    }

    public function testUnknownWorkflowIsNotFound(): void
    {
        $problem = $this->request('GET', '/workflows/00000000-0000-0000-0000-000000000000', null, 404);

        self::assertSame(404, $problem['status']);
    }

    public function testInvalidPayloadReportsViolations(): void
    {
        $problem = $this->request('POST', '/workflows', [
            'name' => '',
            'stepConfiguration' => [[
                'name' => 'no code',
            ]],
        ], 422);

        $paths = array_column($problem['violations'], 'propertyPath');
        self::assertContains('name', $paths);
        self::assertContains('stepConfiguration[0][code]', $paths);
    }

    public function testUnknownStepCodeIsRejected(): void
    {
        $problem = $this->request('POST', '/workflows', [
            'name' => 'Broken',
            'stepConfiguration' => [[
                'code' => 'does.not.exist',
            ]],
        ], 422);

        self::assertStringContainsString('does.not.exist', $problem['detail'] ?? $problem['hydra:description'] ?? '');
    }

    public function testStepsCatalogListsRegisteredSteps(): void
    {
        $codes = array_column($this->members($this->request('GET', '/executable_steps')), 'code');

        self::assertContains('test.uppercase', $codes);
        self::assertContains('etl.extractor.csv_file', $codes);

        $step = $this->request('GET', '/executable_steps/etl.extractor.csv_file');
        self::assertSame('etl.extractor.csv_file', $step['code']);
        self::assertArrayHasKey('configurationSchema', $step);

        $this->request('GET', '/executable_steps/does.not.exist', null, 404);
    }
}
