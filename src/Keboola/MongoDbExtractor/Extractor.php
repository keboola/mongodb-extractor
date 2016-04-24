<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;

class Extractor extends \Keboola\DbExtractor\Extractor\Extractor
{
    /**
     * Tries to ping database server
     * @param $params
     * @return Manager
     */
    public function createConnection($params)
    {
        $manager = new Manager('mongodb://' . $params['host'] .':' . $params['port']);
        $manager->executeCommand('local', new Command(['ping' => 1]));

        return $manager;
    }

    /**
     * Perform execution of all export commands
     * @param array $commands
     * @return bool
     * @throws \Exception
     */
    public function export(array $commands)
    {
        foreach ($commands as $command) {
            /** @var MongoExportCommand $command */
            $process = new Process($command->getCommand());
            $process->mustRun();
            
            (new Filesystem)->dumpFile(
                $command->getOutputFileName() . '.manifest',
                $command->getManifestOptions()
            );
        }

        return true;
    }
}
