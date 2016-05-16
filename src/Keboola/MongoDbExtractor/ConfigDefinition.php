<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Keboola\DbExtractor\Configuration\ConfigDefinition as DbExtractorConfigDefinition;

class ConfigDefinition extends DbExtractorConfigDefinition
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('parameters');

        $rootNode
            ->children()
                ->arrayNode('db')
                    ->children()
                        ->scalarNode('host')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('port')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('database')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('password')->end()
                        ->scalarNode('#password')->end()
                        ->append($this->addSshNode())
                    ->end()
                ->end()
                ->arrayNode('exports')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('id')->end()
                            ->scalarNode('name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('collection')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('query')

                            ->end()
                            ->scalarNode('sort')->end()
                            ->scalarNode('limit')->end()
                            ->booleanNode('enabled')
                                ->defaultValue(true)
                            ->end()
                            ->scalarNode('incremental')->end()
                            ->variableNode('mapping')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
