<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class Export
{
    /** @var MongoExportCommandCsv */
    private $exportCommand;

    /** @var array */
    private $connectionOptions;

    /** @var array */
    private $exportOptions;

    /** @var string */
    private $path;

    /** @var string */
    private $name;

    public function __construct(array $connectionOptions, array $exportOptions, $path, $name)
    {
        $this->connectionOptions = $connectionOptions;
        $this->exportOptions = $exportOptions;
        $this->path = $path;
        $this->name = $name;

        $this->createCommand();
    }

    /**
     * Runs export command
     */
    public function export()
    {
        (new Process($this->exportCommand->getCommand()))->mustRun();
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

        // handle encrypted password
        if (isset($options['#password'])) {
            $options['password'] = $options['#password'];
        }

        // compatibility with db-extractor-common (accepting "user" param)
        if (isset($options['user'])) {
            $options['username'] = $options['user'];
        }

        $options['out'] = $this->getOutputFilename();

        $this->exportCommand = new MongoExportCommandCsv(array_merge($options, $this->exportOptions));
    }

    /**
     * Gets output file name
     * @return string
     */
    public function getOutputFilename()
    {
        return $this->path . '/' . $this->name . '.csv';
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
