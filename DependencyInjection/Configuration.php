<?php

namespace Masilia\ClamavBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('masilia_clamav');

        $rootNode
            ->children()
                ->scalarNode('socket_path')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('root_path')->defaultValue('')->end()
                ->scalarNode('ezform_builder_enabled')->defaultValue(false)->end()
                ->scalarNode('enable_stream_scan')->defaultValue(false)->end()
                ->scalarNode('enable_binary_field_type_validator')->defaultValue(false)->end()
            ->end()
        ;

        return $treeBuilder;
    }

}
