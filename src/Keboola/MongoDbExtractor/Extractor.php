<?php

namespace Keboola\MongoDbExtractor;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;
use Keboola\DbExtractor\Logger;

class Extractor extends \Keboola\DbExtractor\Extractor\Extractor
{
    /** @var Manager */
    protected $db;

    /** @var array */
    private $parameters;

    /**
     * Mapping:
     * - compatibility with db-extractor-common
     * - encrypted password
     * @var array
     */
    private $dbParamsMapping = [
        'user' => 'username',
        'database' => 'db',
        '#password' => 'password',
    ];

    public function __construct($parameters, Logger $logger)
    {
        $this->parameters = $parameters;

        foreach ($this->dbParamsMapping as $from => $to) {
            if (isset($this->parameters['db'][$from])) {
                $this->parameters['db'][$to] = $this->parameters['db'][$from];
            }
        }

        parent::__construct($this->parameters, $logger);
    }

    /**
     * Creates connection
     * @param $params
     * @return Manager
     */
    public function createConnection($params)
    {
        $uri = ['mongodb://'];

        if (isset($params['username'], $params['password'])) {
            $uri[] = $params['username'] . ':' . $params['password'] . '@';
        }

        $uri[] = $params['host'] .':' . $params['port'] . '/' . $params['db'];

        $manager = new Manager(implode('', $uri));

        return $manager;
    }

    /**
     * Sends ping command to database
     */
    public function testConnection()
    {
        $this->db->executeCommand($this->parameters['db']['database'], new Command(['ping' => 1]));
    }

    /**
     * Creates exports and runs extraction
     * @param $outputPath
     * @return bool
     * @throws \Exception
     */
    public function extract($outputPath)
    {
        $count = 0;

        foreach ($this->parameters['exports'] as $exportOptions) {
            $export = new Export(
                $this->parameters['db'],
                $exportOptions,
                $outputPath,
                $exportOptions['name'],
                $exportOptions['mapping']
            );

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
