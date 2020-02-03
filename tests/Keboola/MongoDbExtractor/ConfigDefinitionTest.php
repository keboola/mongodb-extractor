<?php

namespace Keboola\MongoDbExtractor;

use Keboola\MongoDbExtractor\Config\ConfigDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ConfigDefinitionTest extends \PHPUnit\Framework\TestCase
{
    public function testValidConfig()
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "127.0.0.1",
      "port": 27017,
      "database": "test",
      "user": "user",
      "password": "password"
    },
    "exports": [
      {
        "name": "bronx-bakeries",
        "id": 123,
        "collection": "restaurants",
        "query": "{borough: \"Bronx\"}",
        "incremental": false,
        "mapping": {
          "_id": null
        }
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);
        $processor = new Processor;
        $processedConfig = $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);

        $this->assertInternalType('array', $processedConfig);

        // Protocol key is optional, test default value
        $this->assertSame(ConfigDefinition::PROTOCOL_MONGO_DB, $processedConfig['db']['protocol']);
    }

    public function testValidConfigWithProtocol()
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "protocol": "mongodb+srv",
      "host": "127.0.0.1",
      "port": 27017,
      "database": "test",
      "user": "user",
      "password": "password"
    },
    "exports": [
      {
        "name": "bronx-bakeries",
        "id": 123,
        "collection": "restaurants",
        "query": "{borough: \"Bronx\"}",
        "incremental": false,
        "mapping": {
          "_id": null
        }
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);
        $processor = new Processor;
        $processedConfig = $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);

        $this->assertInternalType('array', $processedConfig);
        $this->assertSame(ConfigDefinition::PROTOCOL_MONGO_DB_SRV, $processedConfig['db']['protocol']);
    }

    public function testMissingKeys()
    {
        $this->expectException(InvalidConfigurationException::class);

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "127.0.0.1",
      "database": "test"
    },
    "exports": [
      {
        "name": "bronx-bakeries",
        "collection": "restaurants"
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);
        (new Processor())->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }

    public function testInvalidProtocol()
    {
        $this->expectException(InvalidConfigurationException::class);

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "protocol": "mongodb+error",
      "host": "127.0.0.1",
      "port": 27017,
      "database": "test",
      "user": "user",
      "password": "password"
    },
    "exports": [
      {
        "name": "bronx-bakeries",
        "id": 123,
        "collection": "restaurants",
        "query": "{borough: \"Bronx\"}",
        "incremental": false,
        "mapping": {
          "_id": null
        }
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);
        (new Processor())->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }


}
