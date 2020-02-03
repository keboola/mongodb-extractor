<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Config\ConfigDefinition;
use Symfony\Component\Config\Definition\Processor;

class Application
{
    /** @var array */
    private $config;

    /** @var array */
    private $parameters;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->parameters = (new Processor)->processConfiguration(
            new ConfigDefinition,
            [$this->config['parameters']]
        );
        if (count($this->parameters['exports'])
            !== count(array_unique(array_column($this->parameters['exports'], 'name')))) {
            throw new UserException('Please remove duplicate export names');
        }
    }

    /**
     * Runs data extraction
     * @param $outputPath
     * @return bool
     * @throws \Exception
     */
    public function actionRun(string $outputPath): bool
    {
        $extractor = new Extractor($this->parameters);
        return $extractor->extract($outputPath);
    }

    /**
     * Tests connection
     * @return array
     */
    public function actionTestConnection(): array
    {
        $extractor = new Extractor($this->parameters);
        $extractor->testConnection();
        return [
            'status' => 'ok',
        ];
    }
}
