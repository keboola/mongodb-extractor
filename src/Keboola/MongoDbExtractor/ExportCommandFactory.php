<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Config\ConfigDefinition;

class ExportCommandFactory
{
    /** @var UriFactory */
    private $uriFactory;

    public function __construct(UriFactory $uriFactory)
    {
        $this->uriFactory = $uriFactory;
    }

    public function create(array $params): string
    {
        $protocol = $params['protocol'] ?? ConfigDefinition::PROTOCOL_MONGO_DB;
        $command = ['mongoexport'];

        // Connection options
        if ($protocol === ConfigDefinition::PROTOCOL_MONGO_DB_SRV) {
            // mongodb+srv:// can be used only in URI parameter
            $uri = $this->uriFactory->create($params);
            $command[] = '--uri ' . escapeshellarg($uri);
        } else {
            // If not mongodb+srv://, then use standard parameters: --host, --db, ...
            // because --uri parameter does not work well with some MongoDB servers (probably a bug).
            // In that case:
            // .... test Connection through PHP driver works OK
            // .... mongoexport with --host parameter works OK
            // .... mongoexport with --uri parameter freezes without writing an error
            // Therefore is --uri parameter used only with mongodb+srv://, where there is no other way.
            $command[] = '--host ' . escapeshellarg($params['host']);
            $command[] = '--port ' . escapeshellarg((string) $params['port']);
            $command[] = '--db ' . escapeshellarg($params['database']);

            if (isset($params['user'])) {
                $command[] = '--username ' . escapeshellarg($params['user']);
                $command[] = '--password ' . escapeshellarg($params['password']);
            }

            if (isset($params['authenticationDatabase'])
                && !empty(trim((string) $params['authenticationDatabase']))
            ) {
                $command[] = '--authenticationDatabase ' . escapeshellarg($params['authenticationDatabase']);
            }
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
