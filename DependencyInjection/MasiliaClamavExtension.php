<?php

namespace Masilia\ClamavBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class MasiliaClamavExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        if (true === $config['ezform_builder_enabled']) {
            $loader->load('form_builder_mappers.yml');
        }
        
        if (true === $config['enable_binary_field_type_validator']) {
            $loader->load('binary_fieldtype.yml');
        }
        

        $container->setParameter('socket_path', $config['socket_path']);
        $container->setParameter('root_path', $config['root_path']);
        $container->setParameter('enable_stream_scan', $config['enable_stream_scan']);
        $container->setParameter('enable_binary_field_type_validator', $config['enable_binary_field_type_validator']);
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $isEnabled = $container->getExtensionConfig('masilia_clamav')[0]['ezform_builder_enabled'] ?? false;
        if ($isEnabled) {
            $config = Yaml::parseFile(
                __DIR__.'/../Resources/config/form_field_definition.yml'
            );
            $container->prependExtensionConfig('ez_platform_form_builder', $config);

        }
    }
}
