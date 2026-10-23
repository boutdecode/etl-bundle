<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Mapping;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\InMemoryWorkflow;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessor;

/**
 * Hides an InMemoryWorkflow from Doctrine: it is not an entity, so the pipeline is persisted
 * (and compared on later flushes) as if it had no workflow.
 */
final readonly class InMemoryWorkflowPropertyAccessor implements PropertyAccessor
{
    public function __construct(
        private PropertyAccessor $inner,
    ) {
    }

    public function setValue(object $object, mixed $value): void
    {
        $this->inner->setValue($object, $value);
    }

    public function getValue(object $object): mixed
    {
        $value = $this->inner->getValue($object);

        return $value instanceof InMemoryWorkflow ? null : $value;
    }

    public function getUnderlyingReflector(): \ReflectionProperty
    {
        return $this->inner->getUnderlyingReflector();
    }
}
