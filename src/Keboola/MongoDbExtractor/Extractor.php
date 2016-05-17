<?php

namespace Keboola\MongoDbExtractor;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;

class Extractor extends \Keboola\DbExtractor\Extractor\Extractor
{
    /** @var Manager */
    protected $db;

    /**
     * Tries to ping database server
     * @param $params
     * @return Manager
     */
    public function createConnection($params)
    {
        $manager = new Manager('mongodb://' . $params['host'] .':' . $params['port']);

        return $manager;
    }

    /**
     * Tests connection
     */
    public function testConnection()
    {
        $this->db->executeCommand('local', new Command(['ping' => 1]));
    }

    /**
     * Perform execution of all export commands
     * @param Export[] $exports
     * @return bool
     * @throws \Exception
     */
    public function export(array $exports)
    {
        $count = 0;

        foreach ($exports as $export) {
            if ($export->isEnabled()) {
                $count++;
                $export->export();
                $export->parseAndCreateManifest();
            }
        }

        if ($count === 0) {
            throw new \Exception('Please enable at least one export');
        }

        return true;
    }
}
