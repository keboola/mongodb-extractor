<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/common.php';

use Keboola\MongoDbExtractor\Application;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Keboola\MongoDbExtractor\UserException;
use Keboola\CsvMap\Exception\BadDataException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

$logger = new Logger('app-errors', [new ErrorLogHandler]);

try {
    $arguments = getopt('', ['data:']);
    if (!isset($arguments['data'])) {
        throw new Exception('Data folder not set.');
    }

    $configFile = $arguments['data'] . '/config.json';
    if (!file_exists($configFile)) {
        throw new Exception('Config file not found.');
    }

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
} catch (UserException $e) {
    echo $e->getMessage();
    exit(1);
} catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    echo $e->getMessage();
    exit(1);
} catch (Symfony\Component\Config\Definition\Exception\InvalidConfigurationException $e) {
    echo $e->getMessage() . '. Please check connection settings.';
    exit(1);
} catch (BadDataException $e) {
    echo $e->getMessage()
        . '. Please check mapping section.';
    exit(1);
} catch (\Exception $e) {
    $logger->error($e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    exit(2);
}
