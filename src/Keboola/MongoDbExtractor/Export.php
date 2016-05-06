<?php

namespace Keboola\MongoDbExtractor;

use Keboola\CsvMap\Mapper;
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
     * Parses exported json and creates .csv and .manifest files
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     */
    public function parseAndCreateManifest()
    {
        $data = json_decode(file_get_contents($this->getOutputFilename()));

        $parser = new Mapper($this->mapping, $this->name);
        $parser->parse($data);

        foreach ($parser->getCsvFiles() as $file) {
            $outputCsv = $this->path . '/' . $file->getName() . '.csv';
            $this->fs->copy($file->getPathname(), $outputCsv);

            $manifest = [
                'primary_key' => $file->getPrimaryKey(true),
                'incremental' => isset($this->exportOptions['incremental'])
                    ? (bool) $this->exportOptions['incremental']
                    : false,
            ];

            $this->fs->dumpFile($outputCsv . '.manifest', Yaml::dump($manifest));
            $this->fs->remove($file->getPathname());
        }
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
}
