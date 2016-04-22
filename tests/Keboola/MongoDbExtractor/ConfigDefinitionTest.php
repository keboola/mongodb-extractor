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
    user: user
    password: password
  exports:
    - name: bronx-bakeries
      db: 'test'
      collection: 'restaurants'
      query: '{borough: "Bronx"}'
      fields:
        - name
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
  exports:
    - name: bronx-bakeries
      db: 'test'
      collection: 'restaurants'
YAML;

        $config = Yaml::parse($yaml);
        (new Processor())->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }
}
