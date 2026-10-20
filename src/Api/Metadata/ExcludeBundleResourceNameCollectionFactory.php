<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Api\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;

/**
 * Hides this bundle's resources from API Platform when `boutdecode_etl.api.enabled` is false.
 *
 * API Platform scans the `Entity` directory of every registered bundle on its own, so the
 * `#[ApiResource]` attributes of the entities cannot be left out of the mapping: they have to
 * be filtered out of the discovered resources instead.
 */
final readonly class ExcludeBundleResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private const NAMESPACE = 'BoutDeCode\\ETLBundle\\';

    public function __construct(
        private ResourceNameCollectionFactoryInterface $decorated,
    ) {
    }

    public function create(): ResourceNameCollection
    {
        $classes = [];
        foreach ($this->decorated->create() as $class) {
            if (! str_starts_with($class, self::NAMESPACE)) {
                $classes[] = $class;
            }
        }

        return new ResourceNameCollection($classes);
    }
}
