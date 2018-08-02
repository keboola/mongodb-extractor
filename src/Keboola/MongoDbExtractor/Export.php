<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Parser\Mapping;
use Keboola\MongoDbExtractor\Parser\Raw;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
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

    public function __construct(
        array $connectionOptions,
        array $exportOptions,
        string $path,
        string $name,
        array $mapping
    ) {
        // check mapping section
        if ($exportOptions['mode'] === 'mapping' && empty($mapping)) {
            throw new UserException('Mapping cannot be empty in "mapping" export mode.');
        }

        $this->connectionOptions = $connectionOptions;
        $this->exportOptions = $exportOptions;
        $this->path = $path;
        $this->name = Strings::webalize($name);
        $this->mapping = $mapping;
        $this->filesystem = new Filesystem;
        $this->consoleOutput = new ConsoleOutput;

        $this->createCommand();
    }

    /**
     * Runs export command
     */
    public function export(): void
    {
        $process = new Process($this->exportCommand->getCommand(), null, null, null, null);
        $process->mustRun(function ($type, $buffer): void {
            // $type is always Process::ERR here, so we don't check it
            $this->consoleOutput->write($buffer);
        });
    }

    /**
     * Parses exported json and creates .csv and .manifest files
     * @throws \Keboola\CsvMap\Exception\BadDataException
     * @throws \Keboola\Csv\Exception
     */
    public function parseAndCreateManifest(): void
    {
        $this->consoleOutput->writeln('Parsing "' . $this->getOutputFilename() . '"');

        $manifestOptions = [
            'incremental' => (bool) ($this->exportOptions['incremental'] ?? false),
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
            $data = trim((string) $line) !== '' ? [json_decode($line)] : [];

            $parser->parse($data);

            if ($parsedRecordsCount % 5e3 === 0) {
                $this->consoleOutput->writeln('Parsed ' . $parsedRecordsCount . ' records.');
            }

            $parsedRecordsCount++;
        }

        // TODO: refactor this to be able to write manifest here for both export types
        if ($this->exportOptions['mode'] === 'raw') {
            $parser->writeManifestFile();
        }

        $this->filesystem->remove($this->getOutputFilename());

        $this->consoleOutput->writeln('Done "' . $this->getOutputFilename() . '"');
    }

    /**
     * Creates command
     */
    private function createCommand(): void
    {
        $options = $this->connectionOptions;
        $options['out'] = $this->getOutputFilename();

        $this->exportCommand = new MongoExportCommandJson(array_merge($options, $this->exportOptions));
    }

    /**
     * Gets output file name
     * @return string
     */
    public function getOutputFilename(): string
    {
        return $this->path . '/' . $this->name . '.json';
    }

    /**
     * Returns if export is enabled
     * @return bool
     */
    public function isEnabled(): bool
    {
        return isset($this->exportOptions['enabled']) && $this->exportOptions['enabled'] === true;
    }
}
