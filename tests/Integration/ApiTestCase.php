<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected const ENVIRONMENT = 'test';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient([
            'debug' => false,
            'environment' => static::ENVIRONMENT,
        ]);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    protected function request(string $method, string $uri, ?array $body = null, int $expectedStatus = 200): array
    {
        $this->client->request(
            $method,
            '/etl' . $uri,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            content: $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR),
        );

        $response = $this->client->getResponse();
        self::assertSame($expectedStatus, $response->getStatusCode(), (string) $response->getContent());

        $content = (string) $response->getContent();

        return $content === '' ? [] : json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Members of a Hydra collection.
     *
     * @param array<string, mixed> $collection
     *
     * @return array<int, array<string, mixed>>
     */
    protected function members(array $collection): array
    {
        return $collection['member'] ?? $collection['hydra:member'] ?? self::fail('Not a collection: ' . json_encode($collection));
    }

    /**
     * @param array<string, mixed> $collection
     */
    protected function total(array $collection): int
    {
        return $collection['totalItems'] ?? $collection['hydra:totalItems'] ?? self::fail('No total in: ' . json_encode($collection));
    }

    /**
     * @return array<string, mixed>
     */
    protected function createUppercaseWorkflow(): array
    {
        return $this->request('POST', '/workflows', [
            'name' => 'Shout',
            'description' => 'Uppercases the input',
            'stepConfiguration' => [[
                'code' => 'test.uppercase',
                'name' => 'shout',
            ]],
        ], 201);
    }
}
