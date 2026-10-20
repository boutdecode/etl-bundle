<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Factory;

use BoutDeCode\ETLBundle\Entity\Step;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\StepFactory as StepFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(StepFactoryInterface::class)]
final class StepFactory implements StepFactoryInterface
{
    public function create(string $code, ?string $name = null, array $configuration = [], int $order = 0): Step
    {
        return new Step($code, $name, $configuration, $order);
    }
}
