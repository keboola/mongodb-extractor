<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\MongoDbExtractor\Config\ConfigDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ConfigDefinitionTest extends TestCase
{
    public function testValidConfig(): void
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

        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        $processor = new Processor;
        $processedConfig = $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);

        $this->assertIsArray($processedConfig);

        // Protocol key is optional, test default value
        $this->assertSame(ConfigDefinition::PROTOCOL_MONGO_DB, $processedConfig['db']['protocol']);
    }

    public function testValidConfigWithProtocol(): void
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

        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        $processor = new Processor;
        $processedConfig = $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);

        $this->assertIsArray($processedConfig);
        $this->assertSame(ConfigDefinition::PROTOCOL_MONGO_DB_SRV, $processedConfig['db']['protocol']);
    }

    public function testMissingKeys(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "127.0.0.1"
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

        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        (new Processor())->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }

    public function testMissingUri(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "protocol": "custom_uri",
      "database": "db",
      "password": "pass"
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

        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        (new Processor())->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }

    public function testInvalidProtocol(): void
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

        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        (new Processor())->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }

    /**
     * @dataProvider invalidIncrementalFetchingConfig
     */
    public function testInvalidIncrementalFetchingConfig(string $json, string $expectedMessage): void
    {
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        $processor = new Processor;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);
        $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);
    }

    public function invalidIncrementalFetchingConfig(): array
    {
        return [
            [
                <<<JSON
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
        "incrementalFetchingColumn": "borough",
        "incremental": false,
        "mapping": {
          "_id": null
        }
      }
    ]
  }
}
JSON
                ,
                'Both incremental fetching and query cannot be set together.',
            ],
            [
                <<<JSON
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
        "sort": "_id",
        "incrementalFetchingColumn": "borough",
        "incremental": false,
        "mapping": {
          "_id": null
        }
      }
    ]
  }
}
JSON
                ,
                'Both incremental fetching and sort cannot be set together.',
            ],
        ];
    }

    public function testIncrementalFetchingColumnNormalization(): void
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "127.0.0.1",
      "database": "test",
      "user": "user",
      "password": "password"
    },
    "exports": [
      {
        "name": "bronx-bakeries",
        "collection": "restaurants",
        "incrementalFetchingColumn": "someColumn"
      },
      {
        "name": "bronx-bakeries",
        "collection": "restaurants",
        "incrementalFetchingColumn": "someColumn.\$date"
      },
      {
        "name": "bronx-bakeries",
        "collection": "restaurants",
        "incrementalFetchingColumn": "someColumn.nested.\$date"
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        $processor = new Processor;
        $processedConfig = $processor->processConfiguration(new ConfigDefinition, [$config['parameters']]);
        $this->assertSame('someColumn', $processedConfig['exports'][0]['incrementalFetchingColumn']);
        $this->assertSame('someColumn', $processedConfig['exports'][1]['incrementalFetchingColumn']);
        $this->assertSame('someColumn.nested', $processedConfig['exports'][2]['incrementalFetchingColumn']);
    }
}
