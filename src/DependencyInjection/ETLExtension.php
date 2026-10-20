<?php

declare(strict_types=1);

namespace BoutDeCode\ETLBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class ETLExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'boutdecode_etl';
    }

    public function prepend(ContainerBuilder $container): void
    {
        /** @var array{api: array{enabled: bool}} $config */
        $config = $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));

        if ($config['api']['enabled'] && $container->hasExtension('api_platform')) {
            $container->prependExtensionConfig('api_platform', [
                'mapping' => [
                    'paths' => [__DIR__ . '/../Entity', __DIR__ . '/../Api/Resource'],
                ],
            ]);
        }

        if (! $container->hasExtension('doctrine')) {
            return;
        }

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'BoutDeCodeETLBundle' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__ . '/../Entity',
                        'prefix' => 'BoutDeCode\ETLBundle\Entity',
                        'alias' => 'BoutDeCodeETL',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param array<array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{api: array{enabled: bool}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('boutdecode_etl.api.enabled', $config['api']['enabled']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        if ($config['api']['enabled']) {
            $loader->load('api.yaml');
        } else {
            $loader->load('api_disabled.yaml');
        }
    }
}
