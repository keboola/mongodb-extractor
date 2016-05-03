<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

use Keboola\DbExtractor\Logger;

use Keboola\MongoDbExtractor\ConfigDefinition;
use Keboola\MongoDbExtractor\ExportJson;
use Keboola\MongoDbExtractor\ExtractorJson;

$arguments = getopt('', ['data:']);
if (!isset($arguments['data'])) {
    echo 'Data folder not set.' . "\n";
    exit(2);
}

$configFile = $arguments['data'] . '/config.yml';
if (!file_exists($configFile)) {
    echo 'Config file not found' . "\n";
    exit(2);
}

define('ROOT_PATH', __DIR__ . '/..');

try {
    $config = Yaml::parse(file_get_contents($arguments['data'] . '/config.yml'));
    $outputPath = $arguments['data'] . '/out/tables';

    $parameters = (new Processor)->processConfiguration(new ConfigDefinition, [$config['parameters']]);

    if (count($parameters['exports']) !== count(array_unique(array_column($parameters['exports'], 'name')))) {
        throw new Exception('Please remove duplicate export names');
    }

    $exports = [];
    foreach ($parameters['exports'] as $exportOptions) {
        $exports[] = new ExportJson($parameters['db'], $exportOptions, $outputPath, $exportOptions['name']);
    }

    $extractor = new ExtractorJson($parameters, new Logger('keboola.ex-mongodb'));
    $extractor->export($exports);

    exit(0);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
