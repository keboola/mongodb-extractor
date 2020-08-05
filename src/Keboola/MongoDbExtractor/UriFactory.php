<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Config\ConfigDefinition;
use League\Uri\Exceptions\SyntaxError;

class UriFactory
{
    public function create(array $params): Uri
    {
        $protocol = $params['protocol']  ?? ConfigDefinition::PROTOCOL_MONGO_DB;

        try {
            return $protocol === ConfigDefinition::PROTOCOL_CUSTOM_URI ?
                $this->fromCustomUri($params) :
                $this->fromParams($protocol, $params);
        } catch (SyntaxError $e) {
            throw new UserException('Failed to parse MongoDB URI: ' . $e->getMessage(), 0, $e);
        }
    }

    private function fromCustomUri(array $params): Uri
    {
        $uri = Uri::createFromString($params['uri']);

        if (!$uri->hasUser()) {
            throw new UserException('Connection URI must contain user, eg: "mongodb://user@hostname/database".');
        }

        if ($uri->hasPassword()) {
            throw new UserException(
                'Connection URI must not contain the password. ' .
                'The password is a separate item for security reasons.'
            );
        }

        if (!$uri->hasDatabase()) {
            throw new UserException(
                'Connection URI must contain the database, eg: "mongodb://user@hostname/database".'
            );
        }

        $uri->setPassword($params['password']);

        return $uri;
    }

    private function fromParams(string $protocol, array $params): Uri
    {
        if ($protocol === ConfigDefinition::PROTOCOL_MONGO_DB && empty($params['port'])) {
            // Validate port, required for mongodb://, optional/ignored for mongodb+srv://
            throw new UserException('Missing connection parameter "port".');
        } elseif ($protocol === ConfigDefinition::PROTOCOL_MONGO_DB_SRV) {
            // URI starting with mongodb+srv:// must not include a port number
            $params['port'] = null;
        }

        $query = [];
        if (isset($params['user'], $params['password'], $params['authenticationDatabase'])
            && !empty(trim((string) $params['authenticationDatabase']))
        ) {
            $query[] = ['authSource', $params['authenticationDatabase']];
        }

        return Uri::createFromParts(
            $protocol,
            $params['user'] ?? null,
            $params['password'] ?? null,
            $params['host'],
            !empty($params['port']) ? (int) $params['port']  : null,
            $params['database'],
            $query
        );
    }
}
