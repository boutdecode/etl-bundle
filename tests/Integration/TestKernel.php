<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\Tests\Integration;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use BoutDeCode\ETLBundle\BoutDeCodeETLBundle;
use BoutDeCode\ETLCoreBundle\BoutDeCodeETLCoreBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new ApiPlatformBundle();
        yield new BoutDeCodeETLCoreBundle();
        yield new BoutDeCodeETLBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir() . '/vendor/boutdecode/etl-core-bundle/src/Resources/config/config.yaml');

        $container->loadFromExtension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
            'validation' => [
                'enabled' => true,
            ],
            'serializer' => [
                'enabled' => true,
            ],
            'property_info' => [
                'enabled' => true,
            ],
            'messenger' => [
                // The core declares two buses, so the host application must pick a default one.
                'default_bus' => 'messenger.bus.default',
                'buses' => [
                    'messenger.bus.default' => [],
                ],
                // The core routes its messages to "sync" and "async" transports. Running both inline
                // with `sync://` makes executions assertable without a worker.
                'transports' => [
                    'sync' => 'sync://',
                    'async' => 'sync://',
                ],
            ],
        ]);

        // The "test_no_api" environment checks that the API can be switched off.
        $container->loadFromExtension('boutdecode_etl', [
            'api' => [
                'enabled' => $this->environment !== 'test_no_api',
            ],
        ]);

        $container->loadFromExtension('api_platform', [
            // API Platform scans `%kernel.project_dir%/src/Entity` by default, which is this bundle's own
            // entity directory here. Point it elsewhere so only the bundle's own mapping can enable the API.
            'mapping' => [
                'paths' => [__DIR__ . '/Fixtures'],
            ],
            'title' => 'ETL test',
            'formats' => [
                'jsonld' => ['application/ld+json'],
                'json' => ['application/json'],
            ],
            'docs_formats' => [
                'jsonld' => ['application/ld+json'],
            ],
            'enable_swagger_ui' => false,
            'enable_re_doc' => false,
        ]);

        $container->loadFromExtension('doctrine', [
            'dbal' => [
                'url' => $_SERVER['ETL_TEST_DATABASE_URL'] ?? $_ENV['ETL_TEST_DATABASE_URL'],
            ],
            'orm' => [
                'enable_native_lazy_objects' => true,
            ],
        ]);

        $container->register('test.fixtures.uppercase_step', Fixtures\UppercaseStep::class)
            ->setAutoconfigured(true)
            ->setPublic(true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('.', 'api_platform')->prefix('/etl');
    }
}
