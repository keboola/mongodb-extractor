<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Filesystem\Filesystem;

class ApplicationRawModeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Filesystem */
    private $fs;

    protected $path = '/tmp/application-raw-mode-test';

    protected function setUp()
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->fs->remove($this->path);
    }

    public function testActionRunRawMode()
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
        "collection": "restaurants",
        "query": "{borough: \"Bronx\", \"address.street\": \"Westchester Avenue\"}",
        "sort": "{_id: 1}",
        "limit": 3,
        "incremental": true,
        "mode": "raw"
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path);

        // csv
        $expectedCsvFileMain = $this->path . '/restaurants.csv';
        $expectedCsvMain = <<<CSV
"id","data"
"5716054bee6e764c94fa841e","{""_id"":{""\$oid"":""5716054bee6e764c94fa841e""},""address"":{""building"":""1484"",""coord"":[-73.8806669,40.8283447],""street"":""Westchester Avenue"",""zipcode"":""10472""},""borough"":""Bronx"",""cuisine"":""Bakery"",""name"":""Nacional Bakery #1""}"
"5716054bee6e764c94fa8c13","{""_id"":{""\$oid"":""5716054bee6e764c94fa8c13""},""address"":{""building"":""104512"",""coord"":[-73.885417,40.82766],""street"":""Westchester Avenue"",""zipcode"":""10459""},""borough"":""Bronx"",""cuisine"":""Bakery"",""name"":""La Nueva Giralda Bakery""}"
"5716054bee6e764c94fa8ff6","{""_id"":{""\$oid"":""5716054bee6e764c94fa8ff6""},""address"":{""building"":""1522-4"",""coord"":[-73.8789604,40.8286012],""street"":""Westchester Avenue"",""zipcode"":""10472""},""borough"":""Bronx"",""cuisine"":""Bakery"",""name"":""National Bakery""}"\n
CSV;
        $this->assertFileExists($expectedCsvFileMain);
        $this->assertEquals($expectedCsvMain, file_get_contents($expectedCsvFileMain));

        // manifest
        $actualJsonFileMain = $this->path . '/restaurants.csv.manifest';
        $expectedJsonMain = <<<JSON
{"primary_key":["id"],"incremental":true}
JSON;
        $this->assertFileExists($actualJsonFileMain);
        $this->assertEquals($expectedJsonMain, file_get_contents($actualJsonFileMain));
    }

    public function testActionRunRawModeIdAsString()
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
        "name": "restaurants-id-as-string",
        "id": 123,
        "collection": "restaurantsIdAsString",
        "sort": "{_id: 1}",
        "incremental": true,
        "mode": "raw"
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path);

        // csv
        $expectedCsvFileMain = $this->path . '/restaurants-id-as-string.csv';
        $expectedCsvMain = <<<CSV
"id","data"
"5716054bee6e764c94fa7ddd","{""_id"":""5716054bee6e764c94fa7ddd"",""address"":{""building"":""1007"",""coord"":[-73.856077,40.848447],""street"":""Morris Park Ave"",""zipcode"":""10462""},""borough"":""Bronx"",""cuisine"":""Bakery"",""name"":""Morris Park Bake Shop""}"
"5716054bee6e764c94fa8181","{""_id"":""5716054bee6e764c94fa8181"",""address"":{""building"":""4202"",""coord"":[-73.8569408,40.8936238],""street"":""White Plains Road"",""zipcode"":""10466""},""borough"":""Bronx"",""cuisine"":""Bakery"",""name"":""E & L Bakery & Coffee Shop""}"
"5716054bee6e764c94fa8213","{""_id"":""5716054bee6e764c94fa8213"",""address"":{""building"":""29"",""coord"":[-73.8611922,40.8338023],""street"":""Hugh Grant Circle"",""zipcode"":""10462""},""borough"":""Bronx"",""cuisine"":""Bakery"",""name"":""Zaro'S Bread Basket""}"\n
CSV;
        $this->assertFileExists($expectedCsvFileMain);
        $this->assertEquals($expectedCsvMain, file_get_contents($expectedCsvFileMain));

        // manifest
        $actualJsonFileMain = $this->path . '/restaurants-id-as-string.csv.manifest';
        $expectedJsonMain = <<<JSON
{"primary_key":["id"],"incremental":true}
JSON;
        $this->assertFileExists($actualJsonFileMain);
        $this->assertEquals($expectedJsonMain, file_get_contents($actualJsonFileMain));
    }

    public function testActionRunRawModeMixedIds()
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb-3",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "dataset-mixed-ids",
        "id": 123,
        "collection": "mixedIds",
        "sort": "{_id: 1}",
        "incremental": false,
        "mode": "raw"
      }
    ]
  }
}
JSON;

        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path);

        // csv
        $expectedCsvFileMain = $this->path . '/dataset-mixed-ids.csv';
        $expectedCsvMain = <<<CSV
"id","data"
"123","{""_id"":123}"
"123.456","{""_id"":123.456}"
"123456","{""_id"":""123456""}"
"94fa7ddd","{""_id"":""94fa7ddd""}"
"94fa8181","{""_id"":""94fa8181""}"
"94fa8213","{""_id"":""94fa8213""}"
"","{""_id"":{""key"":""value""}}"
"5716054bee6e764c94fa7ddd","{""_id"":{""\$oid"":""5716054bee6e764c94fa7ddd""}}"
"5716054bee6e764c94fa8181","{""_id"":{""\$oid"":""5716054bee6e764c94fa8181""}}"\n
CSV;
        $this->assertFileExists($expectedCsvFileMain);
        $this->assertEquals($expectedCsvMain, file_get_contents($expectedCsvFileMain));

        // manifest
        $actualJsonFileMain = $this->path . '/dataset-mixed-ids.csv.manifest';
        $expectedJsonMain = <<<JSON
{"primary_key":[],"incremental":false}
JSON;
        $this->assertFileExists($actualJsonFileMain);
        $this->assertEquals($expectedJsonMain, file_get_contents($actualJsonFileMain));
    }
}
