<?php

namespace Keboola\MongoDbExtractor;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;

class Extractor extends \Keboola\DbExtractor\Extractor\Extractor
{
    /**
     * Tries to ping database server
     * @param $params
     * @return Manager
     */
    public function createConnection($params)
    {
        $manager = new Manager('mongodb://' . $params['host'] .':' . $params['port']);
        $manager->executeCommand('local', new Command(['ping' => 1]));

        return $manager;
    }

    /**
     * Perform execution of all export commands
     * @param Export[] $exports
     * @return bool
     */
    public function export(array $exports)
    {
        foreach ($exports as $export) {
            $export->export();
            $export->parseAndCreateManifest();
        }

        return true;
    }
}
