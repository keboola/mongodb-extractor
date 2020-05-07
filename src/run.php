<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/common.php';

use Keboola\CsvMap\Exception\CsvMapperException;
use Keboola\MongoDbExtractor\Application;
use Keboola\MongoDbExtractor\UserException;
use MongoDB\Driver\Exception\AuthenticationException;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Keboola\SSHTunnel\SSHException;

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

    $jsonDecode = new JsonDecode([JsonDecode::ASSOCIATIVE => true]);
    $config = $jsonDecode->decode(
        file_get_contents($arguments['data'] . '/config.json'),
        JsonEncoder::FORMAT
    );
    $outputPath = $arguments['data'] . '/out/tables';

    //get the state
    $inputState = [];
    $inputStateFile = $arguments['data'] . '/in/state.json';
    if (file_exists($inputStateFile)) {
        $inputState = $jsonDecode->decode(
            file_get_contents($inputStateFile),
            JsonEncoder::FORMAT
        );
    }

    $application = new Application($config, $inputState);
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
} catch (UserException|ConnectionTimeoutException|AuthenticationException|SSHException $e) {
    echo $e->getMessage();
    exit(1);
} catch (ProcessFailedException $e) {
    // we do not print exception's message here, it can contain sensitive information
    exit(1);
} catch (InvalidConfigurationException $e) {
    echo $e->getMessage() . '. Please check connection settings.';
    exit(1);
} catch (CsvMapperException $e) {
    echo $e->getMessage()
        . '. Please check mapping section.';
    exit(1);
} catch (\Throwable $e) {
    $logger->error($e->getMessage(), [
        'class' => get_class($e),
        'trace' => $e->getTraceAsString(),
    ]);
    exit(2);
}
