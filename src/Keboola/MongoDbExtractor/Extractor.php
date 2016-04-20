<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Process\Process;

class Extractor extends \Keboola\DbExtractor\Extractor\Extractor
{
    
    public function createConnection($params)
    {
        return true;
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

            if ($process->getExitCode() !== 0) {
                throw new \Exception('Command execution failed');
            }
        }

        return true;
    }
}
