<?php

namespace ALC\RestEntityManager\DependencyInjection;

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
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('alc_rest_entity_manager');

        $rootNode
            ->children()
                ->scalarNode('default_manager')->end()
                ->arrayNode('managers')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                    ->children()
                        ->scalarNode('name')->end()
                        ->scalarNode('host')->end()
                        ->scalarNode('session_name')->end()
                        ->scalarNode('session_timeout')->defaultValue(3600)->end()
                        ->variableNode('custom_params')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
