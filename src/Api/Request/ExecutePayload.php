<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Optional body of the "execute workflow" operation.
 */
final readonly class ExecutePayload
{
    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $input
     */
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        public array $configuration = [],
        public array $input = [],
    ) {
    }
}
