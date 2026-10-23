<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\ETL\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use BoutDeCode\ETLBundle\ETL\Infrastructure\Api\State\ExecutableStepProvider;

/**
 * Read-only catalog of the executable steps registered in the container.
 * The `code` is what a workflow step refers to.
 */
#[ApiResource(
    shortName: 'ExecutableStep',
    operations: [
        new GetCollection(paginationEnabled: false, provider: ExecutableStepProvider::class),
        new Get(uriTemplate: '/executable_steps/{code}', requirements: [
            'code' => '[^/]+',
        ], provider: ExecutableStepProvider::class),
    ],
    formats: ['jsonld', 'json'],
)]
final class ExecutableStepResource
{
    /**
     * @param array<string, mixed> $description
     * @param array<string, mixed> $configurationSchema
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $code,
        public array $description = [],
        public array $configurationSchema = [],
    ) {
    }
}
