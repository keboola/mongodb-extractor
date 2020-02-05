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

        // Validate port, required for mongodb://, optional/ignored for mongodb+srv://
        // URI starting with mongodb+srv:// must not include a port number
        if ($protocol === ConfigDefinition::PROTOCOL_MONGO_DB) {
            if (empty($params['port'])) {
                throw new UserException('Missing connection parameter "port".');
            }

            $uri[] = ':' . $params['port'];
        }

        $uri[] = '/' . $params['database'];

        if (isset($params['username'], $params['password'], $params['authenticationDatabase'])
            && !empty(trim((string) $params['authenticationDatabase']))
        ) {
            $uri[] = '?authSource=' . $params['authenticationDatabase'];
        }

        return implode('', $uri);
    }
}
