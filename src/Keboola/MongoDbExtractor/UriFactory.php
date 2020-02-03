<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Config\ConfigDefinition;

class UriFactory
{
    public function create(array $params): string
    {
        $protocol = $params['protocol']  ?? ConfigDefinition::PROTOCOL_MONGO_DB;
        $uri = [$protocol . '://'];

        if (isset($params['username'], $params['password'])) {
            $uri[] = rawurlencode($params['username']) . ':' . rawurlencode($params['password']) . '@';
        }

        $uri[] = $params['host'];

        // URI starting with mongodb+srv:// must not include a port number
        $uri[] = $protocol === ConfigDefinition::PROTOCOL_MONGO_DB_SRV ? '' : (':' . $params['port']);

        $uri[] = '/' . $params['db'];

        if (isset($params['username'], $params['password'], $params['authenticationDatabase'])
            && !empty(trim((string) $params['authenticationDatabase']))
        ) {
            $uri[] = '?authSource=' . $params['authenticationDatabase'];
        }

        return implode('', $uri);
    }
}
