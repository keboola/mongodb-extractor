<?php

namespace Keboola\MongoDbExtractor;

class MongoExportCommandCsv
{
    /** @var array */
    private $options;

    /** @var string */
    private $command;

    /** @var array */
    private $requiredOptions = [
        'host',
        'port',
        'db',
        'collection',
        'fields',
        'out'
    ];

    public function __construct(array $options)
    {
        $this->options = $options;

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

    /**
     * Gets options
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Validates export options
     * @return bool
     * @throws MongoExportCommandCsvException
     */
    private function validate()
    {
        array_walk($this->requiredOptions, function ($option) {
            if (!isset($this->options[$option])) {
                throw new MongoExportCommandCsvException('Please provide all required params: '
                    . implode(', ', $this->requiredOptions));
            }
        });

        // validate auth options: both or none
        if (isset($this->options['username']) && !isset($this->options['password'])
            || !isset($this->options['username']) && isset($this->options['password'])) {
            throw new MongoExportCommandCsvException('When passing authentication details, both "user" and "password" params are required');
        }

        return true;
    }

    /**
     * Creates command
     */
    private function create()
    {
        $command = [
            'mongoexport'
        ];

        // connection options
        $command[] = '--host ' . escapeshellarg($this->options['host']);
        $command[] = '--port ' . escapeshellarg($this->options['port']);

        if (isset($this->options['username'])) {
            $command[] = '--username ' . escapeshellarg($this->options['username']);
            $command[] = '--password ' . escapeshellarg($this->options['password']);
        }

        // export options
        $command[] = '--db ' . escapeshellarg($this->options['db']);
        $command[] = '--collection ' . escapeshellarg($this->options['collection']);
        $command[] = '--fields ' . escapeshellarg(implode(',', $this->options['fields']));

        foreach (['query', 'sort', 'limit'] as $option) {
            if (isset($this->options[$option])) {
                $command[] = '--' . $option . ' ' . escapeshellarg($this->options[$option]);
            }
        }

        $command[] = '--type ' . escapeshellarg('csv');
        $command[] = '--out ' . escapeshellarg($this->options['out']);

        $this->command = implode(' ', $command);
    }
}
