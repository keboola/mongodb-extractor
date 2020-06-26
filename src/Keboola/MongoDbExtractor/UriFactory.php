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

        if (isset($params['user'], $params['password'])) {
            $uri[] = rawurlencode($params['user']) . ':' . rawurlencode($params['password']) . '@';
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

        $uri[] = '/' . rawurlencode($params['database']);

        if (isset($params['user'], $params['password'], $params['authenticationDatabase'])
            && !empty(trim((string) $params['authenticationDatabase']))
        ) {
            $uri[] = '?authSource=' . rawurlencode($params['authenticationDatabase']);
        }

        return implode('', $uri);
    }
}
