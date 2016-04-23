<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

use Keboola\DbExtractor\Logger;

use Keboola\MongoDbExtractor\ConfigDefinition;
use Keboola\MongoDbExtractor\MongoExportCommand;
use Keboola\MongoDbExtractor\Extractor;

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

    $processor = new Processor;
    $parameters = $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);

    if (isset($parameters['db']['#password'])) {
        $parameters['db']['password'] = $parameters['db']['#password'];
    }

    $exports = [];
    $exportNames = [];

    foreach ($parameters['exports'] as $exportParams) {
        $exports[] = new MongoExportCommand($parameters['db'], $exportParams, $outputPath);
        $exportNames[$exportParams['name']] = $exportParams['name'];
    }

    if (count($parameters['exports']) !== count($exportNames)) {
        throw new Exception('Please remove duplicate export names');
    }

    $extractor = new Extractor($parameters, new Logger('keboola.ex-mongodb'));
    $extractor->export($exports);

    exit(0);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
