<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Api\Request;

use Cron\CronExpression;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class PlannedTaskPayload
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
        #[Assert\NotBlank]
        public string $workflowId,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $schedule,
        public bool $enabled = true,
        public array $input = [],
        public array $configuration = [],
    ) {
    }

    #[Assert\Callback]
    public function validateSchedule(ExecutionContextInterface $context): void
    {
        if ($this->schedule !== '' && ! CronExpression::isValidExpression($this->schedule)) {
            $context->buildViolation('This value is not a valid cron expression.')
                ->atPath('schedule')
                ->addViolation();
        }
    }
}
