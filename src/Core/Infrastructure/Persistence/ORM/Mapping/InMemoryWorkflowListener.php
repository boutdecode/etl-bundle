<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Mapping;

use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Pipeline;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Guards the unit of work against the InMemoryWorkflow of a pipeline, see {@see InMemoryWorkflowPropertyAccessor}.
 *
 * Done on flush because the property accessors are only built once the metadata are loaded,
 * i.e. after the loadClassMetadata event.
 */
#[AsDoctrineListener(event: Events::preFlush)]
final class InMemoryWorkflowListener
{
    public function preFlush(PreFlushEventArgs $args): void
    {
        $metadata = $args->getObjectManager()->getClassMetadata(Pipeline::class);
        $accessor = $metadata->propertyAccessors['workflow'] ?? null;

        if ($accessor === null || $accessor instanceof InMemoryWorkflowPropertyAccessor) {
            return;
        }

        $metadata->propertyAccessors['workflow'] = new InMemoryWorkflowPropertyAccessor($accessor);
    }
}
