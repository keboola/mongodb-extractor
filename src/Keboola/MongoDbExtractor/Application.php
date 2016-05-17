<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Config\Definition\Processor;
use Keboola\DbExtractor\Logger;

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
    }

    /**
     * Runs data extraction
     * @param $outputPath
     * @return bool
     * @throws \Exception
     */
    public function actionRun($outputPath)
    {
        if (count($this->parameters['exports']) !== count(array_unique(array_column($this->parameters['exports'], 'name')))) {
            throw new \Exception('Please remove duplicate export names');
        }

        $exports = [];
        foreach ($this->parameters['exports'] as $exportOptions) {
            $exports[] = new Export(
                $this->parameters['db'],
                $exportOptions,
                $outputPath,
                $exportOptions['name'],
                $exportOptions['mapping']
            );
        }

        $extractor = new Extractor($this->parameters, new Logger('keboola.ex-mongodb'));
        return $extractor->export($exports);
    }

    /**
     * Tests connection
     * @return array
     */
    public function actionTestConnection()
    {
        $extractor = new Extractor($this->parameters, new Logger('keboola.ex-mongodb'));
        $extractor->testConnection();
        return [
            'status' => 'ok'
        ];
    }
}
