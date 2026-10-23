<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Unit\Core\Infrastructure\Api\Request;

use BoutDeCode\ETLBundle\Core\Infrastructure\Api\Request\PlannedTaskPayload;
use BoutDeCode\ETLBundle\Core\Infrastructure\Api\Request\WorkflowPayload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class PayloadValidationTest extends TestCase
{
    public function testValidWorkflowPayload(): void
    {
        $payload = new WorkflowPayload('Import', stepConfiguration: [[
            'code' => 'a',
            'configuration' => [
                'x' => 1,
            ],
            'order' => 0,
        ]]);

        self::assertCount(0, $this->validate($payload));
    }

    public function testInvalidWorkflowPayload(): void
    {
        $payload = new WorkflowPayload('', stepConfiguration: [[
            'name' => 'missing code',
        ], [
            'code' => 'a',
            'order' => 'first',
        ]]);

        $paths = array_map(static fn ($violation): string => $violation->getPropertyPath(), [...$this->validate($payload)]);

        self::assertContains('name', $paths);
        self::assertContains('stepConfiguration[0][code]', $paths);
        self::assertContains('stepConfiguration[1][order]', $paths);
    }

    #[DataProvider('cronExpressions')]
    public function testPlannedTaskSchedule(string $schedule, bool $valid): void
    {
        $violations = $this->validate(new PlannedTaskPayload('Task', 'workflow-id', $schedule));

        self::assertSame($valid, count($violations) === 0);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function cronExpressions(): iterable
    {
        yield 'nightly' => ['0 3 * * *', true];
        yield 'macro' => ['@daily', true];
        yield 'garbage' => ['whenever', false];
        yield 'too few fields' => ['* * *', false];
    }

    private function validate(object $payload): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($payload);
    }
}
