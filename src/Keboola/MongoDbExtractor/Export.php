<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class Export
{
    /** @var MongoExportCommandJson */
    private $exportCommand;

    /** @var array */
    private $connectionOptions;

    /** @var array */
    private $exportOptions;

    /** @var string */
    private $path;

    /** @var string */
    private $name;

    /** @var array */
    private $mapping;

    /** @var Filesystem */
    private $fs;

    /**
     * Mapping:
     * - compatibility with db-extractor-common
     * - encrypted password
     * @var array
     */
    private $connectionOptionsMapping = [
        'user' => 'username',
        'database' => 'db',
        '#password' => 'password',
    ];

    public function __construct(array $connectionOptions, array $exportOptions, $path, $name, $mapping)
    {
        $this->connectionOptions = $connectionOptions;
        $this->exportOptions = $exportOptions;
        $this->path = $path;
        $this->name = $name;
        $this->mapping = $mapping;
        $this->fs = new Filesystem;

        foreach ($this->connectionOptionsMapping as $from => $to) {
            if (isset($this->connectionOptions[$from])) {
                $this->connectionOptions[$to] = $this->connectionOptions[$from];
            }
        }

        $this->createCommand();
    }

    /**
     * Runs export command
     */
    public function export()
    {
        $process = new Process($this->exportCommand->getCommand(), null, null, null, null);
        $process->mustRun();
    }

    /**
     * Creates manifest file
     */
    public function createManifest()
    {
        (new Filesystem)->dumpFile(
            $this->getOutputFilename() . '.manifest',
            Yaml::dump($this->getManifestOptions())
        );
    }

    /**
     * Creates command
     */
    private function createCommand()
    {
        $options = $this->connectionOptions;
        $options['out'] = $this->getOutputFilename();

        $this->exportCommand = new MongoExportCommandJson(array_merge($options, $this->exportOptions));
    }

    /**
     * Gets output file name
     * @return string
     */
    public function getOutputFilename()
    {
        return $this->path . '/' . $this->name . '.json';
    }

    /**
     * Gets output path
     * @return string
     */
    public function getOutputPath()
    {
        return $this->path;
    }

    /**
     * Gets export name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets manifest file options
     * @return array
     */
    public function getManifestOptions()
    {
        $config = [
            'incremental' => true,
        ];

        if (isset($this->exportOptions['incremental'])) {
            $config['incremental'] = (bool) $this->exportOptions['incremental'];
        }
        if (isset($this->exportOptions['primaryKey'])) {
            $config['primary_key'] = $this->exportOptions['primaryKey'];
        }

        return $config;
    }
}
