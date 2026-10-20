<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use BoutDeCode\ETLBundle\Entity\Pipeline;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\PurgePipelineCommand;

/**
 * Removes a pipeline through the core, which also removes its history.
 *
 * @implements ProcessorInterface<Pipeline, null>
 */
final readonly class PipelineDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $this->commandBus->dispatch(new PurgePipelineCommand($data->getId()));

        return null;
    }
}
