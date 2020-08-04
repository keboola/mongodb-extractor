<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Config;

use Keboola\MongoDbExtractor\UserException;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigDefinition implements ConfigurationInterface
{
    public const PROTOCOL_MONGO_DB = 'mongodb';

    // https://docs.mongodb.com/manual/reference/connection-string/#dns-seedlist-connection-format
    public const PROTOCOL_MONGO_DB_SRV = 'mongodb+srv';

    public const PROTOCOL_CUSTOM_URI = 'custom_uri';


    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('parameters');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('db')
                    ->validate()
                        ->always(function (array $v) {
                            $protocol = $v['protocol'];
                            $sshTunnelEnabled = $v['ssh']['enabled'] ?? false;
                            $v['password'] = $v['password'] ?? $v['#password'] ?? null;

                            if ($protocol === self::PROTOCOL_CUSTOM_URI) {
                                // Validation for "custom_uri" protocol
                                if (!isset($v['uri'])) {
                                    throw new UserException(
                                        'The child node "uri" at path "parameters.db" must be configured.'
                                    );
                                }

                                // SSH tunnel cannot be used with custom URI
                                if ($sshTunnelEnabled) {
                                    throw new UserException(
                                        'Custom URI is not compatible with SSH tunnel support.'
                                    );
                                }

                                // Check incompatible keys
                                foreach (['host', 'port', 'database', 'authenticationDatabase'] as $key) {
                                    if (isset($v[$key])) {
                                        throw new UserException(sprintf(
                                            'Configuration node "db.%s" is not compatible with custom URI.',
                                            $key
                                        ));
                                    }
                                }
                            } else {
                                // Validation for "mongodb" or "mongodb+srv" protocol
                                if (!isset($v['host'])) {
                                    throw new UserException(
                                        'The child node "host" at path "parameters.db" must be configured.'
                                    );
                                }

                                if (!isset($v['database'])) {
                                    throw new UserException(
                                        'The child node "database" at path "parameters.db" must be configured.'
                                    );
                                }

                                // Validate auth options: both or none
                                if (isset($v['user']) xor isset($v['password'])) {
                                    throw new UserException(
                                        'When passing authentication details,' .
                                        ' both "user" and "password" params are required'
                                    );
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->enumNode('protocol')
                            ->values([self::PROTOCOL_MONGO_DB, self::PROTOCOL_MONGO_DB_SRV, self::PROTOCOL_CUSTOM_URI])
                            ->defaultValue(self::PROTOCOL_MONGO_DB)
                        ->end()
                        ->scalarNode('uri')->cannotBeEmpty()->end()
                        ->scalarNode('host')->cannotBeEmpty()->end()
                        ->scalarNode('port')->cannotBeEmpty()->end()
                        ->scalarNode('database')->cannotBeEmpty()->end()
                        ->scalarNode('authenticationDatabase')->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('password')->end()
                        ->scalarNode('#password')->end()
                        ->append($this->addSshNode())
                    ->end()
                ->end()
                ->arrayNode('exports')
                    ->prototype('array')
                        ->validate()
                        ->always(function ($v) {
                            if (isset($v['query']) && $v['query'] !== '' && isset($v['incrementalFetchingColumn'])) {
                                throw new InvalidConfigurationException(
                                    'Both incremental fetching and query cannot be set together.'
                                );
                            }
                            if (isset($v['sort']) && $v['sort'] !== '' && isset($v['incrementalFetchingColumn'])) {
                                $message = 'Both incremental fetching and sort cannot be set together.';
                                throw new InvalidConfigurationException($message);
                            }
                            return $v;
                        })
                        ->end()
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
                            ->scalarNode('incrementalFetchingColumn')->end()
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
        $builder = new TreeBuilder('ssh');

        $builder->getRootNode()
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

        return $builder->getRootNode();
    }
}
