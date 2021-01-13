<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Config\ConfigDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class Application
{
    private array $config;

    private array $parameters;

    private Extractor $extractor;

    public function __construct(array $config, array $inputState = [])
    {
        $this->config = $config;

        try {
            $this->parameters = (new Processor)->processConfiguration(
                new ConfigDefinition,
                [$this->config['parameters']]
            );
        } catch (InvalidConfigurationException $e) {
            throw new UserException($e->getMessage(), 0, $e);
        }

        if (count($this->parameters['exports'])
            !== count(array_unique(array_column($this->parameters['exports'], 'name')))) {
            throw new UserException('Please remove duplicate export names');
        }

        $uriFactory = new UriFactory();
        $exportCommandFactory = new ExportCommandFactory($uriFactory, $this->parameters['quiet']);
        $this->extractor = new Extractor($uriFactory, $exportCommandFactory, $this->parameters, $inputState);
    }

    /**
     * Runs data extraction
     * @param $outputPath
     * @return bool
     * @throws \Exception
     */
    public function actionRun(string $outputPath): bool
    {
        return $this->extractor->extract($outputPath);
    }

    /**
     * Tests connection
     * @return array
     */
    public function actionTestConnection(): array
    {
        $this->extractor->testConnection();
        return [
            'status' => 'ok',
        ];
    }
}
