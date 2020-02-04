<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\UriFactory;

class ExportCommandFactory
{
    public function create(array $params): string
    {
        $uriFactory = new UriFactory();
        $uri = $uriFactory->create($params);

        $command = ['mongoexport'];
        $command[] = '--uri ' . escapeshellarg($uri);
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
