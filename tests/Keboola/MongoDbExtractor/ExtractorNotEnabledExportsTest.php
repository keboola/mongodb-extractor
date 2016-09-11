<?php

namespace Keboola\MongoDbExtractor;

use Keboola\DbExtractor\Logger;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ExtractorNotEnabledExportsTest extends \PHPUnit_Framework_TestCase
{
    private $logger;

    protected $path = '/tmp/extractor-not-enabled-exports';

    protected function setUp()
    {
        $this->logger = new Logger('keboola.ex-mongodb');
    }

    protected function getConfig()
    {
        $config = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "enabled": false,
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
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);

    }

    public function testWrongConnection()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please enable at least one export');

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $extractor->extract($this->path);
    }
}
