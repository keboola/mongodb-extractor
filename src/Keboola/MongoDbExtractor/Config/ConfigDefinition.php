<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class ConfigDefinition implements ConfigurationInterface
{
    public const
        PROTOCOL_MONGO_DB = 'mongodb',
        PROTOCOL_MONGO_DB_SRV = 'mongodb+srv'; // https://docs.mongodb.com/manual/reference/connection-string/#dns-seedlist-connection-format


    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('parameters');

        $rootNode
            ->children()
                ->arrayNode('db')
                    ->children()
                        ->enumNode('protocol')
                            ->values([self::PROTOCOL_MONGO_DB, self::PROTOCOL_MONGO_DB_SRV])
                            ->defaultValue(self::PROTOCOL_MONGO_DB)
                        ->end()
                        ->scalarNode('host')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('port')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('database')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('authenticationDatabase')->end()
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
                            ->scalarNode('query')->end()
                            ->scalarNode('sort')->end()
                            ->scalarNode('limit')->end()
                            ->enumNode('mode')
                                ->values(['mapping', 'raw'])
                                ->defaultValue('mapping')
                            ->end()
                            ->booleanNode('enabled')
                                ->defaultValue(true)
                            ->end()
                            ->booleanNode('incremental')->end()
                            ->variableNode('mapping')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function addSshNode(): NodeDefinition
    {
        $builder = new TreeBuilder();
        $node = $builder->root('ssh');

        $node
            ->children()
                ->booleanNode('enabled')->end()
                ->arrayNode('keys')
                    ->children()
                        ->scalarNode('private')->end()
                        ->scalarNode('#private')->end()
                        ->scalarNode('public')->end()
                    ->end()
                ->end()
                ->scalarNode('sshHost')->end()
                ->scalarNode('sshPort')->end()
                ->scalarNode('remoteHost')->end()
                ->scalarNode('remotePort')->end()
                ->scalarNode('localPort')->end()
                ->scalarNode('user')->end()
            ->end()
        ;

        return $node;
    }
}
