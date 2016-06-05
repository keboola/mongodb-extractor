<?php

namespace Keboola\MongoDbExtractor;

use Keboola\CsvMap\Mapper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Nette\Utils\Strings;

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

    /** @var ConsoleOutput */
    private $consoleOutput;

    public function __construct(array $connectionOptions, array $exportOptions, $path, $name, $mapping)
    {
        $this->connectionOptions = $connectionOptions;
        $this->exportOptions = $exportOptions;
        $this->path = $path;
        $this->name = $name;
        $this->mapping = $mapping;
        $this->fs = new Filesystem;
        $this->consoleOutput = new ConsoleOutput;

        $this->createCommand();
    }

    /**
     * Runs export command
     */
    public function export()
    {
        $process = new Process($this->exportCommand->getCommand(), null, null, null, null);
        $process->mustRun(function ($type, $buffer) {
            // $type is always Process::ERR here, so we don't check it
            $this->consoleOutput->write($buffer);
        });
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
            if ($file !== null) {
                $name = Strings::webalize($file->getName());

                $this->consoleOutput->writeln(date('Y-m-d\TH:i:sO') . "\t" . 'Parsing "' . $name . '"');

                $outputCsv = $this->path . '/' . $name . '.csv';
                $this->fs->copy($file->getPathname(), $outputCsv);

                $manifest = [
                    'primary_key' => $file->getPrimaryKey(true),
                    'incremental' => isset($this->exportOptions['incremental'])
                        ? (bool) $this->exportOptions['incremental']
                        : false,
                ];

                $this->fs->dumpFile($outputCsv . '.manifest', Yaml::dump($manifest));
                $this->fs->remove($file->getPathname());

                $this->consoleOutput->writeln(date('Y-m-d\TH:i:sO') . "\t" . 'Done "' . $name . '"');
            }
        }

        $this->fs->remove($this->getOutputFilename());
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
     * Returns if export is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return isset($this->exportOptions['enabled']) && $this->exportOptions['enabled'] === true;
    }
}
