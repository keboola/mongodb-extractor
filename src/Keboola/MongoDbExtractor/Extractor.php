<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\TestConnectionCommandFactory;
use Keboola\SSHTunnel\SSH;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Extractor
{
    /** @var array */
    private $parameters;

    /**
     * Mapping:
     * - compatibility with db-extractor-common
     * - encrypted password
     * @var array
     */
    private $dbParamsMapping = [
        'user' => 'username',
        'database' => 'db',
        '#password' => 'password',
    ];

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;

        foreach ($this->dbParamsMapping as $from => $to) {
            if (isset($this->parameters['db'][$from])) {
                $this->parameters['db'][$to] = $this->parameters['db'][$from];
            }
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

            (new SSH())->openTunnel($sshOptions);

            $this->parameters['db']['host'] = '127.0.0.1';
            $this->parameters['db']['port'] = $sshOptions['localPort'];
        }
    }

    /**
     * Sends listCollections command to test connection/credentials
     */
    public function testConnection(): void
    {
        $uriFactory = new UriFactory();
        $uri = $uriFactory->create($this->parameters['db']);
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
        $count = 0;

        foreach ($this->parameters['exports'] as $exportOptions) {
            $export = new Export(
                $this->parameters['db'],
                $exportOptions,
                $outputPath,
                $exportOptions['name'],
                $exportOptions['mapping'] ?? []
            );

            if ($export->isEnabled()) {
                $count++;
                $export->export();
                $export->parseAndCreateManifest();
            }
        }

        if ($count === 0) {
            throw new UserException('Please enable at least one export');
        }

        return true;
    }
}
