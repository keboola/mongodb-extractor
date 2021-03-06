<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\MongoDbExtractor\Application;
use Keboola\MongoDbExtractor\UserException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Filesystem\Filesystem;

class ApplicationTest extends TestCase
{
    private Filesystem $fs;

    protected string $path = '/tmp/application-test';

    protected function setUp(): void
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->path);
    }

    public function testActionRunFull(string $quiet = 'false'): void
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb",
      "port": 27017,
      "database": "test"
    },
    "quiet": $quiet,
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "query": "{borough: \"Bronx\", \"address.street\": \"Westchester Avenue\"}",
        "sort": "{name: 1, _id: 1}",
        "incremental": true,
        "mapping": {
          "_id.\$oid": {
            "type": "column",
            "mapping": {
              "destination": "id",
              "primaryKey": true
            }
          },
          "name": "name",
          "address": {
            "type": "table",
            "destination": "Bakeries Coords",
            "parentKey": {
              "destination": "bakeries_id"
            },
            "tableMapping": {
              "coord.0": "w",
              "coord.1": "n",
              "zipcode": {
                "type": "column",
                "mapping": {
                  "destination": "zipcode",
                  "primaryKey": true
                }
              },
              "street": {
                "type": "column",
                "mapping": {
                  "destination": "street",
                  "primaryKey": true
                }
              }
            }
          }
        }
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path);

        // main csv
        $expectedCsvFileMain = $this->path . '/bakeries.csv';
        $expectedCsvMain = <<<CSV
"id","name"
"5716054cee6e764c94fa9f31","1617-A National Bakery"
"5716054cee6e764c94fad66f","Jacqueline'S Bakery Restaurant"
"5716054bee6e764c94fa8c13","La Nueva Giralda Bakery"
"5716054bee6e764c94fa841e","Nacional Bakery #1"
"5716054bee6e764c94fa8ff6","National Bakery"
"5716054cee6e764c94faa105","National Bakery"\n
CSV;
        $this->assertFileExists($expectedCsvFileMain);
        $this->assertEquals($expectedCsvMain, file_get_contents($expectedCsvFileMain));

        // main manifest
        $actualJsonFileMain = $this->path . '/bakeries.csv.manifest';
        $expectedJsonMain = <<<JSON
{"primary_key":["id"],"incremental":true}
JSON;
        $this->assertFileExists($actualJsonFileMain);
        $this->assertEquals($expectedJsonMain, file_get_contents($actualJsonFileMain));

        // related csv
        $expectedCsvFileRelated = $this->path . '/bakeries-coords.csv';
        $expectedCsvRelated = <<<CSV
"w","n","zipcode","street","bakeries_id"
"-73.8747516","40.829474","10472","Westchester Avenue","5716054cee6e764c94fa9f31"
"-73.8767078","40.8290734","10472","Westchester Avenue","5716054cee6e764c94fad66f"
"-73.885417","40.82766","10459","Westchester Avenue","5716054bee6e764c94fa8c13"
"-73.8806669","40.8283447","10472","Westchester Avenue","5716054bee6e764c94fa841e"
"-73.8789604","40.8286012","10472","Westchester Avenue","5716054bee6e764c94fa8ff6"
"-73.8510158","40.8342588","10462","Westchester Avenue","5716054cee6e764c94faa105"\n
CSV;
        $this->assertFileExists($expectedCsvFileRelated);
        $this->assertEquals($expectedCsvRelated, file_get_contents($expectedCsvFileRelated));

        // related manifest
        $actualJsonFileRelated = $this->path . '/bakeries-coords.csv.manifest';
        $expectedJsonRelated = <<<JSON
{"primary_key":["zipcode","street"],"incremental":true}
JSON;
        $this->assertFileExists($actualJsonFileRelated);
        $this->assertEquals($expectedJsonRelated, file_get_contents($actualJsonFileRelated));
    }

    public function testActionRunFullQuite(): void
    {
        $this->testActionRunFull('true');
    }

    public function testActionRunDuplicateExportNames(): void
    {

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "locahost",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      },
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Please remove duplicate export names');
        $application = new Application($config);
        $application->actionRun($this->path);
    }

    public function testMissingMappingSection(): void
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "restaurants",
        "id": 123,
        "collection": "restaurants"
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $this->expectException(UserException::class);
        $this->expectExceptionMessageMatches('~Mapping cannot be empty~');
        $application = new Application($config);
        $application->actionRun($this->path);
    }

    public function testValidateWithoutUser(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessageMatches(
            '~When passing authentication details, both "user" and "password" params are required~'
        );

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "locahost",
      "password": "password",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path);
    }

    public function testValidateWithoutPassword(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessageMatches(
            '~When passing authentication details, both "user" and "password" params are required~'
        );

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "locahost",
      "user": "user",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path);
    }

    public function testCreateWithMissingRequiredParam(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessageMatches('~The child node "host" at path "parameters.db" must be configured.~');

        $json = <<<JSON
{
  "parameters": {
    "db": {},
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path);
    }
}
