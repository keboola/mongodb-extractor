<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

class ExportCommandFactory
{
    public function create(array $params): string
    {
        $command = ['mongoexport'];

        // Connection options
        $command[] = '--host ' . escapeshellarg($params['host']);
        $command[] = '--port ' . escapeshellarg((string) $params['port']);
        $command[] = '--db ' . escapeshellarg($params['database']);

        if (isset($params['username'])) {
            $command[] = '--username ' . escapeshellarg($params['username']);
            $command[] = '--password ' . escapeshellarg($params['password']);
        }

        if (isset($params['authenticationDatabase'])
            && !empty(trim((string) $params['authenticationDatabase']))
        ) {
            $command[] = '--authenticationDatabase ' . escapeshellarg($params['authenticationDatabase']);
        }

        // Export options
        $command[] = '--collection ' . escapeshellarg($params['collection']);

        foreach (['query', 'sort', 'limit'] as $option) {
            if (isset($params[$option]) && !empty(trim((string) $params[$option]))) {
                $command[] = '--' . $option . ' ' . escapeshellarg((string) $params[$option]);
            }
        }

        $command[] = '--type ' . escapeshellarg('json');
        $command[] = '--out ' . escapeshellarg($params['out']);

        return implode(' ', $command);
    }
}
