<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use BoutDeCode\ETLBundle\Api\Resource\ExecutableStepResource;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Resolver\StepResolver;
use Webmozart\Assert\Assert;

/**
 * @implements ProviderInterface<ExecutableStepResource>
 */
final readonly class ExecutableStepProvider implements ProviderInterface
{
    public function __construct(
        private StepResolver $stepResolver,
    ) {
    }

    /**
     * @return array<int, ExecutableStepResource>|ExecutableStepResource|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|ExecutableStepResource|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return array_values(array_map($this->resource(...), $this->stepResolver->list()));
        }

        Assert::string($uriVariables['code']);
        $step = $this->stepResolver->resolve($uriVariables['code']);

        return $step === null ? null : $this->resource($step);
    }

    private function resource(ExecutableStep $step): ExecutableStepResource
    {
        return new ExecutableStepResource($step->getCode(), $step->getConfigurationDescription(), $step->getConfigurationSchema());
    }
}
