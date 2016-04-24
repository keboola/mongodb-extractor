<?php

namespace Keboola\MongoDbExtractor;

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

        if ($this->validate()) {
            $this->create();
        }
    }

    /**
     * Gets built command prepared for execution
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    public function getExportOptions()
    {
        return $this->exportParams;
    }

    /**
     * Validates existence of export parameters
     * @return bool
     * @throws MongoExportCommandException
     */
    private function validate()
    {
        $params = array_merge($this->connectionParams, $this->exportParams);
        $requiredParams = ['host', 'port', 'db', 'collection', 'fields', 'name'];

        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                throw new MongoExportCommandException('Please provide all required params: ' . implode(', ', $requiredParams));
            }
        }

        // none or both
        if (isset($this->connectionParams['user']) && !isset($this->connectionParams['password'])
            || !isset($this->connectionParams['user']) && isset($this->connectionParams['password'])) {
            throw new MongoExportCommandException('When connecting with authentication, both "user" and "password" params are required');
        }

        return true;
    }

    public function getOutputFileName()
    {
        return $this->outputPath . '/' . $this->exportParams['name'] . '.csv';
    }

    public function getManifestOptions()
    {
        $config = [
            'incremental' => isset($this->exportParams['incremental'])
                                ? $this->exportParams['incremental']
                                : true,
        ];

        if (isset($this->exportParams['primaryKey'])) {
            $config['primary_key'] = (bool) $this->exportParams['primaryKey'];
        }

        return $config;
    }

    /**
     * Creates command from connection and export params
     */
    private function create()
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

        if (isset($this->connectionParams['user'])) {
            $command[] = '--username';
            $command[] = escapeshellarg($this->connectionParams['user']);

            $command[] = '--password';
            $command[] = escapeshellarg($this->connectionParams['password']);
        }

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

        if (isset($this->exportParams['sort'])) {
            $command[] = '--sort';
            $command[] = escapeshellarg($this->exportParams['sort']);
        }

        if (isset($this->exportParams['limit'])) {
            $command[] = '--limit';
            $command[] = escapeshellarg($this->exportParams['limit']);
        }

        $command[] = '--type';
        $command[] = escapeshellarg('csv');

        $command[] = '--out';
        $command[] = escapeshellarg($this->getOutputFileName());

        $this->command = implode(' ', $command);
    }
}
