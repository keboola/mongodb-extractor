<?php

namespace Keboola\MongoDbExtractor;

use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;
use Keboola\Json\Parser;
use Symfony\Component\Yaml\Yaml;

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
        $fs = new \Symfony\Component\Filesystem\Filesystem;

        foreach ($exports as $export) {
            $export->export();

            $parser = Parser::create(new \Monolog\Logger('json-parser'));
            $json = file_get_contents($export->getOutputFilename());
            $data = json_decode($json);

            $parser->process($data, $export->getName());
            $results = $parser->getCsvFiles();

            foreach ($results as $result) {
                /** @var $result \Keboola\CsvTable\Table */
                $destinationFilename = $export->getOutputPath() . '/' . $result->getName();

                $fs->copy(
                    $result->getPathname(),
                    $destinationFilename
                );

                if ($result->getName() === $export->getName()) {
                    $this->createManifestForMainFile($destinationFilename);
                } else {
                    $this->createManifestForRelatedFile($destinationFilename);
                }
            }

            $fs->remove($export->getOutputFilename());
        }

        return true;
    }

    private function createManifestForMainFile($filepath)
    {
        $fs = new \Symfony\Component\Filesystem\Filesystem;

        $fs->dumpFile(
            $filepath . '.manifest',
            Yaml::dump(['incremental' => true, 'primary_key' => ['id_oid']])
        );
    }

    private function createManifestForRelatedFile($filepath)
    {
        $fs = new \Symfony\Component\Filesystem\Filesystem;

        $manifestOptions = [
            'incremental' => true,
        ];

        $file = fopen($filepath, 'r');
        $header = fgets($file);
        fclose($file);
        $pks = explode(',', $header);
        array_walk($pks, function (&$pk) {
            $pk = trim(trim($pk), '"');
        });
        $manifestOptions['primary_key'] = $pks;

        $fs->dumpFile(
            $filepath . '.manifest',
            Yaml::dump($manifestOptions)
        );
    }
}
