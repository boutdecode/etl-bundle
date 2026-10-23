<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\ETL\Infrastructure\Step\Executor;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\Trait\ExecutableStepAttributeTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

#[AsExecutableStep(
    code: 'etl.executor.console_command',
    configurationDescription: [
        'command' => 'The Symfony console command name to execute (e.g. "app:my-command")',
        'arguments' => 'Associative array of arguments and options to pass to the command',
        'catchExceptions' => 'Whether the application should catch exceptions (default: false)',
    ],
    configurationSchema: [
        'command' => [
            'type' => 'string',
        ],
        'arguments' => [
            'type' => 'object',
        ],
        'catchExceptions' => [
            'type' => 'boolean',
        ],
    ],
)]
final class ConsoleCommandExecutorStep implements ExecutableStep
{
    use ExecutableStepAttributeTrait;

    public function __construct(
        private readonly Application $application,
    ) {
    }

    public function process(Context $context): Context
    {
        $stepConfig = $context->getConfigurationValue($this->getName(), []);
        /** @var array<string, mixed> $config */
        $config = is_array($stepConfig) ? $stepConfig : [];

        $command = $config['command'] ?? $this->configuration['command'] ?? '';
        $arguments = $config['arguments'] ?? $this->configuration['arguments'] ?? [];
        /** @var array<string, mixed> $argumentsArr */
        $argumentsArr = is_array($arguments) ? $arguments : [];

        $result = $this->execute(is_string($command) ? $command : '', $argumentsArr, $context, $config);

        return $context->setResult($this->getName(), $result);
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $configuration
     *
     * @return array{exitCode: int, output: string}
     * @throws \Exception
     */
    public function execute(string $command, array $arguments, Context $context, array $configuration = []): array
    {
        $catchExceptions = $configuration['catchExceptions'] ?? $this->configuration['catchExceptions'] ?? false;

        $input = new ArrayInput(array_merge([
            'command' => $command,
        ], $arguments));
        $output = new BufferedOutput();

        $this->application->setAutoExit(false);
        $this->application->setCatchExceptions((bool) $catchExceptions);

        $exitCode = $this->application->run($input, $output);

        return [
            'exitCode' => $exitCode,
            'output' => $output->fetch(),
        ];
    }
}
