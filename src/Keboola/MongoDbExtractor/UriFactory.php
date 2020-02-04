<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

class UriFactory
{
    /** @var array */
    private $requiredDbOptions = [
        'host',
        'port',
        'db',
    ];

    public function create(array $params): string
    {
        array_walk($this->requiredDbOptions, function ($option) use ($params): void {
            if (!isset($params[$option])) {
                $msg = sprintf('Missing connection parameter "%s".', $option);
                throw new UserException($msg);
            }
        });

        // validate auth options: both or none
        if (isset($params['username']) && !isset($params['password'])
            || !isset($params['username']) && isset($params['password'])) {
            throw new UserException('When passing authentication details,'
                . ' both "user" and "password" params are required');
        }

        $uri = ['mongodb://'];

        if (isset($params['username'], $params['password'])) {
            $uri[] = rawurlencode($params['username']) . ':' . rawurlencode($params['password']) . '@';
        }

        $uri[] = $params['host'] .':' . $params['port'] . '/' . $params['db'];

        if (isset($params['username'], $params['password'], $params['authenticationDatabase'])
            && !empty(trim((string) $params['authenticationDatabase']))
        ) {
            $uri[] = '?authSource=' . $params['authenticationDatabase'];
        }

        return implode('', $uri);
    }
}
