<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\Csv\Exception as CsvException;
use Keboola\MongoDbExtractor\Parser\Mapping;
use Keboola\MongoDbExtractor\Parser\Raw;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Nette\Utils\Strings;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class Export
{
    private ExportCommandFactory $exportCommandFactory;

    private array $connectionOptions;

    private array $exportOptions;

    private string $path;

    private string $name;

    private array $mapping;

    private Filesystem $filesystem;

    private ConsoleOutput $consoleOutput;

    private JsonDecode $jsonDecoder;

    public function __construct(
        ExportCommandFactory $exportCommandFactory,
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

        $this->exportCommandFactory = $exportCommandFactory;
        $this->connectionOptions = $connectionOptions;
        $this->exportOptions = $exportOptions;
        $this->path = $path;
        $this->name = Strings::webalize($name);
        $this->mapping = $mapping;
        $this->filesystem = new Filesystem;
        $this->consoleOutput = new ConsoleOutput;
        $this->jsonDecoder = new JsonDecode;
    }

    /**
     * Runs export command
     */
    public function export(): void
    {
        $options = array_merge(
            $this->connectionOptions,
            $this->exportOptions,
            ['out' => $this->getOutputFilename()]
        );

        $cliCommand = $this->exportCommandFactory->create($options);

        $process = Process::fromShellCommandline($cliCommand, null, null, null, null);

        $lastOutput = null;
        try {
            $process->mustRun(function ($type, $buffer) use (&$lastOutput): void {
                // $type is always Process::ERR here, so we don't check it
                $lastOutput = $buffer;
                $this->consoleOutput->write($buffer);
            });
        } catch (ProcessFailedException $e) {
            if ($lastOutput && strpos($lastOutput, 'Failed: EOF') !== false) {
                throw new UserException(sprintf(
                    'Export \"%s\" failed. Timeout occurred while waiting for data. ' .
                    'Please check your query. Problem can be a typo in the field name or missing index.' .
                    'In these cases, the full scan is made and it can take too long.',
                    $this->name
                ));
            }

            throw $e;
        }
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
            $parser = new Mapping(
                $this->name,
                $this->mapping,
                $this->exportOptions['includeParentInPK'] ?? false,
                $this->path,
                $manifestOptions
            );
        }

        $handle = fopen($this->getOutputFilename(), 'r');

        $parsedDocumentsCount = 0;
        $skippedDocumentsCount = 0;
        while (!feof($handle)) {
            $line = (string) fgets($handle);

            try {
                $data = trim($line) !== ''
                    ? [$this->jsonDecoder->decode($line, JsonEncoder::FORMAT)]
                    : [];
                $parser->parse($data);
                if ($parsedDocumentsCount % 5e3 === 0 && $parsedDocumentsCount !== 0) {
                    $this->consoleOutput->writeln('Parsed ' . $parsedDocumentsCount . ' records.');
                }
            } catch (NotEncodableValueException $notEncodableValueException) {
                $this->consoleOutput->writeln('Could not decode JSON: ' . substr($line, 0, 80) . '...');
                $skippedDocumentsCount++;
            } catch (CsvException $e) {
                throw new UserException(sprintf(
                    'CSV writing error. Header and mapped documents must be scalar values. %s',
                    $e->getMessage()
                ));
            } finally {
                $parsedDocumentsCount++;
            }
        }

        // TODO: refactor this to be able to write manifest here for both export types
        if ($this->exportOptions['mode'] === 'raw') {
            $parser->writeManifestFile();
        }

        $this->filesystem->remove($this->getOutputFilename());

        if ($skippedDocumentsCount !== 0) {
            $this->consoleOutput->writeln('Skipped documents: ' . $skippedDocumentsCount);
        }
        $this->consoleOutput->writeln('Done "' . $this->getOutputFilename() . '"');
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

    /**
     * @return mixed
     */
    public function getLastFetchedValue()
    {
        // Limit can be disabled with empty string
        if (!empty($this->exportOptions['limit'])) {
            $lastvalueOptions = [
                'limit' => 1,
                'skip' => $this->exportOptions['limit']-1,
                'sort' => json_encode([$this->exportOptions['incrementalFetchingColumn'] => 1]),
            ];
        } else {
            $lastvalueOptions = [
                'limit' => 1,
                'sort' => json_encode([$this->exportOptions['incrementalFetchingColumn'] => -1]),
            ];
        }
        $options = array_merge(
            $this->connectionOptions,
            $this->exportOptions,
            $lastvalueOptions
        );

        $cliCommand = $this->exportCommandFactory->create($options);
        $process = Process::fromShellCommandline($cliCommand, null, null, null, null);
        $process->mustRun();

        $output = $process->getOutput();
        if (!empty($output)) {
            // Replace {"$date":"DATE"} to "ISODate("DATE")"
            $output = DateHelper::convertDatesToString($output, true);

            $data = $this->jsonDecoder->decode($output, JsonEncoder::FORMAT, ['json_decode_associative' => true]);
            $incrementalFetchingColumn = explode('.', $this->exportOptions['incrementalFetchingColumn']);
            foreach ($incrementalFetchingColumn as $item) {
                if (!isset($data[$item])) {
                    $fullPathColumnMessage = '';
                    if (count($incrementalFetchingColumn) > 1) {
                        $fullPathColumnMessage = sprintf(
                            ' ("%s")',
                            $this->exportOptions['incrementalFetchingColumn']
                        );
                    }
                    throw new UserException(
                        sprintf(
                            'Column "%s"%s does not exists.',
                            $item,
                            $fullPathColumnMessage
                        )
                    );
                }
                $data = $data[$item];
            }

            if (is_array($data)) {
                throw new UserException(sprintf(
                    'Unexpected value "%s" in output of incremental fetching.',
                    json_encode($data)
                ));
            }

            return $data;
        }
        return null;
    }

    public static function saveStateFile(string $outputPath, array $data): void
    {
        $filename = $outputPath . '/../state.json';
        $saveData = [
            'lastFetchedRow' => $data,
        ];
        file_put_contents($filename, json_encode($saveData));
    }

    /**
     * @param string|int|null $inputState
     */
    public static function buildIncrementalFetchingParams(array $params, $inputState): array
    {
        $query = (object) [];
        if (!is_null($inputState)) {
            $query = [
                $params['incrementalFetchingColumn'] => [
                    '$gte' => $inputState,
                ],
            ];
        }

        $params['query'] = DateHelper::fixIsoDateInGteQuery(json_encode($query));
        $params['sort'] = json_encode([$params['incrementalFetchingColumn'] => 1]);
        return $params;
    }
}
