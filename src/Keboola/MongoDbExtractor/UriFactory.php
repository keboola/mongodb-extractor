<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

class UriFactory
{
    public function create(array $params): string
    {
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
