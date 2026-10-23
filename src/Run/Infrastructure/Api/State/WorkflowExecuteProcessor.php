<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Run\Infrastructure\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Pipeline;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\WorkflowRepository;
use BoutDeCode\ETLBundle\Run\Application\PipelineLauncher;
use BoutDeCode\ETLBundle\Run\Infrastructure\Api\Request\ExecutePayload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * Creates a pipeline from the workflow and queues its execution.
 *
 * The body is optional, so the operation is declared without deserialization and
 * parsed here: an empty body means "run with the workflow defaults".
 *
 * @implements ProcessorInterface<null, Pipeline>
 */
final readonly class WorkflowExecuteProcessor implements ProcessorInterface
{
    public function __construct(
        private WorkflowRepository $workflows,
        private PipelineLauncher $launcher,
        private ValidatorInterface $validator,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Pipeline {
        $workflow = $this->workflows->find($uriVariables['id']) ??
            throw new NotFoundHttpException('Workflow not found.');
        $payload = $this->payload($context['request'] ?? null);

        $pipeline = $this->launcher->launch($workflow, $payload->name, $payload->configuration, $payload->input);
        Assert::isInstanceOf($pipeline, Pipeline::class);

        return $pipeline;
    }

    private function payload(mixed $request): ExecutePayload
    {
        if (! $request instanceof Request || $request->getContent() === '') {
            return new ExecutePayload();
        }

        try {
            $data = $request->toArray();
        } catch (\Throwable $e) {
            throw new BadRequestHttpException('The request body is not valid JSON.', $e);
        }

        $configuration = $data['configuration'] ?? [];
        $input = $data['input'] ?? [];
        Assert::isMap($configuration);
        Assert::isMap($input);

        $payload = new ExecutePayload(is_string($data['name'] ?? null) ? $data['name'] : null, $configuration, $input);

        $violations = $this->validator->validate($payload);
        if (count($violations) > 0) {
            throw new UnprocessableEntityHttpException((string) $violations->get(0)->getMessage(), new ValidationFailedException($payload, $violations));
        }

        return $payload;
    }
}
