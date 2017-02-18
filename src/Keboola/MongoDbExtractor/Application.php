<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Config\Definition\Processor;

class Application
{
    /** @var array */
    private $config;

    /** @var array */
    private $parameters;

    public function __construct($config)
    {
        $this->config = $config;
        $this->parameters = (new Processor)->processConfiguration(
            new ConfigDefinition,
            [$this->config['parameters']]
        );
        if (count($this->parameters['exports']) !== count(array_unique(array_column($this->parameters['exports'], 'name')))) {
            throw new \Exception('Please remove duplicate export names');
        }
    }

    /**
     * Runs data extraction
     * @param $outputPath
     * @return bool
     * @throws \Exception
     */
    public function actionRun($outputPath)
    {
        $extractor = new Extractor($this->parameters);
        return $extractor->extract($outputPath);
    }

    /**
     * Tests connection
     * @return array
     */
    public function actionTestConnection()
    {
        $extractor = new Extractor($this->parameters);
        $extractor->testConnection();
        return [
            'status' => 'ok'
        ];
    }
}
