<?php

require __DIR__ . '/../vendor/autoload.php';

use Keboola\MongoDbExtractor\Application;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

$arguments = getopt('', ['data:']);
if (!isset($arguments['data'])) {
    echo 'Data folder not set.' . "\n";
    exit(2);
}

$configFile = $arguments['data'] . '/config.json';
if (!file_exists($configFile)) {
    echo 'Config file not found' . "\n";
    exit(2);
}

try {
    $jsonDecode = new JsonDecode(true);
    $config = $jsonDecode->decode(
        file_get_contents($arguments['data'] . '/config.json'),
        JsonEncoder::FORMAT
    );
    $outputPath = $arguments['data'] . '/out/tables';

    $application = new Application($config);
    $action = isset($config['action']) ? $config['action'] : 'run';

    switch ($action) {
        case 'testConnection':
            $result = $application->actionTestConnection();
            echo json_encode($result);
            break;
        default:
            $result = $application->actionRun($outputPath);
            break;
    }

    exit(0);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
