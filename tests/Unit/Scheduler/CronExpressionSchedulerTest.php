<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Unit\Scheduler;

use BoutDeCode\ETLBundle\Scheduler\CronExpressionScheduler;
use PHPUnit\Framework\TestCase;

final class CronExpressionSchedulerTest extends TestCase
{
    public function testNextScheduleIsInTheFutureAndMatchesTheExpression(): void
    {
        $next = (new CronExpressionScheduler())->getNextScheduleFromExpression('0 3 * * *');

        self::assertGreaterThan(new \DateTimeImmutable(), $next);
        self::assertSame('03:00', $next->format('H:i'));
    }

    public function testInvalidExpressionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new CronExpressionScheduler())->getNextScheduleFromExpression('nope');
    }
}
