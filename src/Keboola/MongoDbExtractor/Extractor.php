<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\SSHTunnel\SSH;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Manager;

class Extractor
{
    private array $parameters;

    private UriFactory $uriFactory;

    private ExportCommandFactory $exportCommandFactory;

    private array $inputState;

    private array $dbParamsMapping = [
        '#password' => 'password',
    ];

    public function __construct(
        UriFactory $uriFactory,
        ExportCommandFactory $exportCommandFactory,
        array $parameters,
        array $inputState = []
    ) {
        $this->uriFactory = $uriFactory;
        $this->exportCommandFactory = $exportCommandFactory;
        $this->parameters = $parameters;
        $this->inputState = $inputState;

        foreach ($this->dbParamsMapping as $from => $to) {
            if (isset($this->parameters['db'][$from])) {
                $this->parameters['db'][$to] = $this->parameters['db'][$from];
            }
        }

        $dbParams = $this->parameters['db'];

        // Host is required
        if (empty($dbParams['host'])) {
            throw new UserException('Missing connection parameter "host".');
        }

        // Database is required
        if (empty($dbParams['database'])) {
            throw new UserException('Missing connection parameter "db".');
        }

        // validate auth options: both or none
        if (isset($dbParams['user']) && !isset($dbParams['password'])
            || !isset($dbParams['user']) && isset($dbParams['password'])) {
            throw new UserException('When passing authentication details,'
                . ' both "user" and "password" params are required');
        }

        if (isset($this->parameters['db']['ssh']['enabled']) && $this->parameters['db']['ssh']['enabled'] === true) {
            $sshOptions = $this->parameters['db']['ssh'];
            $sshOptions['localPort'] = '33006';

            $privateKey = isset($sshOptions['keys']['#private'])
                ? $sshOptions['keys']['#private']
                : $sshOptions['keys']['private'];
            $sshOptions['privateKey'] = $privateKey;

            $sshOptions['remoteHost'] = $this->parameters['db']['host'];
            $sshOptions['remotePort'] = $this->parameters['db']['port'];

            $this->createSshTunnel($sshOptions);

            $this->parameters['db']['host'] = '127.0.0.1';
            $this->parameters['db']['port'] = $sshOptions['localPort'];
        }
    }

    /**
     * Sends listCollections command to test connection/credentials
     */
    public function testConnection(): void
    {
        $uri = $this->uriFactory->create($this->parameters['db']);
        try {
            $manager = new Manager($uri);
        } catch (Exception $exception) {
            throw new UserException($exception->getMessage(), 0, $exception);
        }
        $manager->executeCommand($this->parameters['db']['database'], new Command(['listCollections' => 1]));
    }

    /**
     * Creates exports and runs extraction
     * @param $outputPath
     * @return bool
     * @throws \Exception
     */
    public function extract(string $outputPath): bool
    {
        $this->testConnection();

        $count = 0;

        $lastFetchedValues = [];
        foreach ($this->parameters['exports'] as $exportOptions) {
            $incrementalFetching = (isset($exportOptions['incrementalFetchingColumn']) &&
                $exportOptions['incrementalFetchingColumn'] !== '');
            if ($incrementalFetching) {
                $lastFetchedValue = $this->inputState['lastFetchedRow'][$exportOptions['id']] ?? null;
                $exportOptions = Export::buildIncrementalFetchingParams(
                    $exportOptions,
                    $lastFetchedValue
                );
            }
            $export = new Export(
                $this->exportCommandFactory,
                $this->parameters['db'],
                $exportOptions,
                $outputPath,
                $exportOptions['name'],
                $exportOptions['mapping'] ?? []
            );

            if ($export->isEnabled()) {
                $count++;
                if ($incrementalFetching) {
                    $lastFetchedValues[$exportOptions['id']] = $export->getLastFetchedValue() ?? $lastFetchedValue;
                }
                $export->export();
                $export->parseAndCreateManifest();
            }
        }

        if (!empty($lastFetchedValues)) {
            Export::saveStateFile($outputPath, $lastFetchedValues);
        }

        if ($count === 0) {
            throw new UserException('Please enable at least one export');
        }

        return true;
    }

    private function createSshTunnel(array $sshOptions): void
    {
        (new SSH())->openTunnel($sshOptions);
    }
}
