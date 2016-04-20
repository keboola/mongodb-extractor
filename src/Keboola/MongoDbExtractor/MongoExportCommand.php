<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Process\Process;

class MongoExportCommand
{
    /** @var array */
    private $connectionParams;

    /** @var array */
    private $exportParams;

    /** @var string */
    private $outputPath;

    /** @var string */
    private $command;

    public function __construct(array $connectionParams, array $exportParams, $outputPath)
    {
        $this->connectionParams = $connectionParams;
        $this->exportParams = $exportParams;
        $this->outputPath = $outputPath;

        $this->create();
    }

    /**
     * Gets built command prepared for execution
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Creates command from connection and export params
     */
    protected function create()
    {
        $command = [
            'mongoexport'
        ];

        /**
         * Connection options
         */
        $command[] = '--host';
        $command[] = escapeshellarg($this->connectionParams['host']);

        $command[] = '--port';
        $command[] = escapeshellarg($this->connectionParams['port']);

        /**
         * Export options like db, collection, query, limit, etc.
         */
        $command[] = '--db';
        $command[] = escapeshellarg($this->exportParams['db']);

        $command[] = '--collection';
        $command[] = escapeshellarg($this->exportParams['collection']);

        $command[] = '--fields';
        $command[] = escapeshellarg(implode(',', $this->exportParams['fields']));

        if (isset($this->exportParams['query'])) {
            $command[] = '--query';
            $command[] = escapeshellarg($this->exportParams['query']);
        }

//        if (isset($this->exportParams['sort'])) {
//            $command[] = '--sort';
//            $command[] = escapeshellarg($this->exportParams['sort']);
//        }
//        if (isset($this->exportParams['limit'])) {
//            $command[] = '--limit';
//            $command[] = escapeshellarg($this->exportParams['limit']);
//        }
//        $command[] = '--type';
//        $command[] = escapeshellarg('csv');

        $command[] = '--csv';

        $command[] = '--out';
        $command[] = escapeshellarg($this->outputPath . '/' . $this->exportParams['name'] . '.csv');

        $this->command = implode(' ', $command);
    }
}
