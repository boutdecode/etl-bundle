<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class WorkflowPayload
{
    /**
     * @param array<string, mixed> $stepConfiguration
     * @param array<string, mixed> $configuration
     * @param string[]|null $notificationProviders
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
        public ?string $description = null,
        #[Assert\All([
            new Assert\Collection(
                fields: [
                    'code' => [
                        new Assert\NotBlank(),
                        new Assert\Type('string')
                    ],
                    'name' => new Assert\Optional([
                        new Assert\Type('string')
                    ]),
                    'configuration' => new Assert\Optional([
                        new Assert\Type('array')
                    ]),
                    'order' => new Assert\Optional([
                        new Assert\Type('int')
                    ]),
                ],
            ),
        ])]
        public array $stepConfiguration = [],
        public array $configuration = [],
        public bool $notifyOnSuccess = false,
        public bool $notifyOnFailure = false,
        #[Assert\All([
            new Assert\Type('string')
        ])]
        public ?array $notificationProviders = null,
    ) {
    }
}
