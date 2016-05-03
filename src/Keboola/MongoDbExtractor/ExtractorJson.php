<?php

namespace Keboola\MongoDbExtractor;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;
use Keboola\Json\Parser;

class ExtractorJson extends \Keboola\DbExtractor\Extractor\Extractor
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
     * @param ExportJson[] $exports
     * @return bool
     */
    public function export(array $exports)
    {
        foreach ($exports as $export) {
            $export->export();

            // json parser
            $parser = Parser::create(new \Monolog\Logger('json-parser'));
            $json = file_get_contents($export->getOutputFilename());
            $data = json_decode($json);

            $parser->process($data);
            $results = $parser->getCsvFiles();
            foreach ($results as $result) {
                /** @var $result \Keboola\Csv\CsvFile */
                $fs = new \Symfony\Component\Filesystem\Filesystem;
                $fs->copy(
                    $result->getFileInfo()->getPathname(),
                    $export->getOutputPath() . '/' . $result->getFileInfo()->getFilename()
                );
            }
        }

        return true;
    }
}
