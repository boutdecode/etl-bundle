<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Scheduler;

use BoutDeCode\ETLCoreBundle\Run\Domain\Scheduler\ExpressionScheduler;
use Cron\CronExpression;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ExpressionScheduler::class)]
final class CronExpressionScheduler implements ExpressionScheduler
{
    public function getNextScheduleFromExpression(string $expression): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable((new CronExpression($expression))->getNextRunDate());
    }
}
