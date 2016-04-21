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
                        ->scalarNode('host')->end()
                        ->scalarNode('port')->end()
                        ->append($this->addSshNode())
                    ->end()
                ->end()
                ->arrayNode('exports')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('db')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('collection')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('query')

                            ->end()
                            ->arrayNode('fields')
                                ->prototype('scalar')->end()
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
