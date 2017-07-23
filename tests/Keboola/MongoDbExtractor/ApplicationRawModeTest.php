<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Filesystem\Filesystem;

class ApplicationRawModeTest extends \PHPUnit_Framework_TestCase
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
}
