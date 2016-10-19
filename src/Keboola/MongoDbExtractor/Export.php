<?php

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Parser\Mapping;
use Keboola\MongoDbExtractor\Parser\Raw;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\JsonEncode;
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

    /** @var JsonEncode */
    private $jsonEncode;

    public function __construct(array $connectionOptions, array $exportOptions, $path, $name, $mapping)
    {
        $this->connectionOptions = $connectionOptions;
        $this->exportOptions = $exportOptions;
        $this->path = $path;
        $this->name = Strings::webalize($name);
        $this->mapping = $mapping;
        $this->filesystem = new Filesystem;
        $this->consoleOutput = new ConsoleOutput;
        $this->jsonEncode = new JsonEncode;

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

        $manifestOptions = [
            'incremental' => (bool) ($this->exportOptions['incremental'] ?? false)
        ];

        if ($this->exportOptions['mode'] === 'raw') {
            $parser = new Raw($this->name, $this->path, $manifestOptions);
        } else {
            $parser = new Mapping($this->name, $this->mapping, $this->path, $manifestOptions);
        }

        $handle = fopen($this->getOutputFilename(), 'r');

        $parsedRecordsCount = 1;
        while (!feof($handle)) {
            $line = fgets($handle);
            $data = trim($line) !== '' ? [json_decode($line)] : [];

            $parser->parse($data);

            if ($parsedRecordsCount % 5e3 === 0) {
                $this->logToConsoleOutput('Parsed ' . $parsedRecordsCount . ' records.');
            }

            $parsedRecordsCount++;
        }

        $this->filesystem->remove($this->getOutputFilename());

        $this->logToConsoleOutput('Done "' . $this->getOutputFilename() . '"');
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
