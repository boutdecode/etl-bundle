<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration\Fixtures;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractTransformerStep;

#[AsExecutableStep(code: 'test.uppercase', configurationDescription: [], configurationSchema: [])]
final class UppercaseStep extends AbstractTransformerStep
{
    public function transform(mixed $data, Context $context, array $configuration = []): mixed
    {
        return array_map(static fn (mixed $value): mixed => is_string($value) ? strtoupper($value) : $value, (array) $data);
    }
}
