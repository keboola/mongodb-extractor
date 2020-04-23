<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\SSHTunnel\SSH;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;

class Extractor
{
    /** @var array */
    private $parameters;

    /** @var UriFactory */
    private $uriFactory;

    /** @var ExportCommandFactory */
    private $exportCommandFactory;

    /** @var array */
    private $inputState;

    /**
     * Mapping:
     * - encrypted password
     * @var array
     */
    private $dbParamsMapping = [
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
        $manager = new Manager($uri);
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
                Export::validateIncrementalFetching(
                    $exportOptions,
                    $this->parameters['db'],
                    $this->exportCommandFactory
                );
                $lastFetchedValue = null;
                if (isset($this->inputState['lastFetchedRow'][$exportOptions['id']])) {
                    $lastFetchedValue = $this->inputState['lastFetchedRow'][$exportOptions['id']];
                }
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
                    $lastFetchedValues[$exportOptions['id']] = $export->getLastFetchedValue();
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
