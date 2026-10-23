<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration\Core\Infrastructure\Api;

use BoutDeCode\ETLBundle\Core\Infrastructure\Api\State\WorkflowProcessor;
use BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Repository\WorkflowRepository;
use BoutDeCode\ETLBundle\Tests\Integration\ApiTestCase;

/**
 * Same kernel with `boutdecode_etl.api.enabled: false`.
 */
final class ApiDisabledTest extends ApiTestCase
{
    protected const ENVIRONMENT = 'test_no_api';

    public function testNoRouteIsExposed(): void
    {
        $this->request('GET', '/workflows', null, 404);
        $this->request('GET', '/pipelines', null, 404);
        $this->request('GET', '/executable_steps', null, 404);
    }

    public function testApiServicesAreNotRegistered(): void
    {
        self::assertFalse(static::getContainer()->has(WorkflowProcessor::class));
        self::assertFalse(static::getContainer()->getParameter('boutdecode_etl.api.enabled'));
    }

    public function testPersistenceStillWorks(): void
    {
        $workflows = static::getContainer()->get('doctrine')->getRepository(\BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Workflow::class);
        self::assertInstanceOf(WorkflowRepository::class, $workflows);

        $workflows->create(new \BoutDeCode\ETLBundle\Core\Infrastructure\Persistence\ORM\Entity\Workflow('Still stored'));

        self::assertCount(1, $workflows->findAll());
    }
}
