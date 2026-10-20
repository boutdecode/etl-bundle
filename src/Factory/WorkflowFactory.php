<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Factory;

use BoutDeCode\ETLBundle\Entity\Workflow;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\WorkflowFactory as WorkflowFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WorkflowFactoryInterface::class)]
final class WorkflowFactory implements WorkflowFactoryInterface
{
    public function create(
        string $name,
        array $configuration = [],
        array $stepConfiguration = [],
        ?string $description = null,
        bool $notifyOnSuccess = false,
        bool $notifyOnFailure = false,
        ?array $notificationProviders = null,
    ): Workflow {
        return new Workflow(
            $name,
            $configuration,
            $stepConfiguration,
            $description,
            $notifyOnSuccess,
            $notifyOnFailure,
            $notificationProviders,
        );
    }
}
