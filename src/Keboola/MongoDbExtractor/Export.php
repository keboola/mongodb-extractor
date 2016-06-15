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
    private $filesystem;

    /** @var ConsoleOutput */
    private $consoleOutput;

    public function __construct(array $connectionOptions, array $exportOptions, $path, $name, $mapping)
    {
        $this->connectionOptions = $connectionOptions;
        $this->exportOptions = $exportOptions;
        $this->path = $path;
        $this->name = $name;
        $this->mapping = $mapping;
        $this->filesystem = new Filesystem;
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
        $this->logToConsoleOutput('Parsing "' . $this->getOutputFilename() . '"');

        $handle = fopen($this->getOutputFilename(), 'r');
        $skipHeader = false;

        $parsedRecordsCount = 1;
        while (!feof($handle)) {
            $line = fgets($handle);
            $data = trim($line) !== '' ? [json_decode($line)] : [];

            $parser = new Mapper($this->mapping, $this->name);
            $parser->parse($data);

            $this->writeCsvAndManifestFiles($parser->getCsvFiles(), $skipHeader);

            $skipHeader = true;

            if ($parsedRecordsCount % 5e3 === 0) {
                $this->logToConsoleOutput('Parsed ' . $parsedRecordsCount . ' records.');
            }

            $parsedRecordsCount++;
        }

        $this->filesystem->remove($this->getOutputFilename());

        $this->logToConsoleOutput('Done "' . $this->getOutputFilename() . '"');
    }

    /**
     * Writes .csv and .manifest files
     * @param array $csvFiles
     * @param bool $skipHeader
     */
    private function writeCsvAndManifestFiles(array $csvFiles, $skipHeader = true)
    {
        foreach ($csvFiles as $file) {
            if ($file !== null) {
                $name = Strings::webalize($file->getName());
                $outputCsv = $this->path . '/' . $name . '.csv';

                $content = file_get_contents($file->getPathname());

                // csv-map don't have option to skip header yet
                if ($skipHeader) {
                    $contentArr = explode("\n", $content);
                    array_shift($contentArr);
                    $content = implode("\n", $contentArr);
                }

                $this->appendContentToFile($outputCsv, $content);

                $manifest = [
                    'primary_key' => $file->getPrimaryKey(true),
                    'incremental' => isset($this->exportOptions['incremental'])
                        ? (bool) $this->exportOptions['incremental']
                        : false,
                ];

                if (!$this->filesystem->exists($outputCsv . '.manifest')) {
                    $this->filesystem->dumpFile($outputCsv . '.manifest', Yaml::dump($manifest));
                }

                $this->filesystem->remove($file->getPathname());
            }
        }
    }

    /**
     * Outputs text to console prefixed with actual time (to look similar as MongoDB log)
     * @param $text
     */
    private function logToConsoleOutput($text)
    {
        $this->consoleOutput->writeln(
            date('Y-m-d\TH:i:s\.')
            . str_pad(round(microtime(true) - time(), 3) * 1000, 3, '0', STR_PAD_LEFT)
            . date('O') . "\t" . $text
        );
    }

    /**
     * Append content to specified file
     * @param $filename
     * @param $content
     * @throws Exception
     */
    private function appendContentToFile($filename, $content)
    {
        if (false === @file_put_contents($filename, $content, FILE_APPEND | LOCK_EX)) {
            throw new Exception(sprintf('Failed to write file "%s".', $filename), 0, null, $filename);
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

    /**
     * Returns if export is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return isset($this->exportOptions['enabled']) && $this->exportOptions['enabled'] === true;
    }
}
