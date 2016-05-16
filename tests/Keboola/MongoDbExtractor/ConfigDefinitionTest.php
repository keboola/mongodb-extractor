<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ConfigDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testValidConfig()
    {
        $yaml = <<<YAML
parameters:
  db:
    host: 127.0.0.1
    port: 27017
    database: test
    user: user
    password: password
  exports:
    - name: bronx-bakeries
      id: 123
      collection: 'restaurants'
      query: '{borough: "Bronx"}'
      incremental: false
      mapping:
        _id:
YAML;

        $config = Yaml::parse($yaml);
        $processor = new Processor;
        $processedConfig = $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);

        $this->assertInternalType('array', $processedConfig);
    }

    public function testInvalidConfig()
    {
        $this->expectException(InvalidConfigurationException::class);

        $yaml = <<<YAML
parameters:
  db:
    host: 127.0.0.1
    database: test
  exports:
    - name: bronx-bakeries
      collection: 'restaurants'
YAML;

        $config = Yaml::parse($yaml);
        (new Processor())->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }
}
