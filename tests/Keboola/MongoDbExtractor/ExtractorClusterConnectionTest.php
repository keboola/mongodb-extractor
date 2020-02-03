<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Test connection using mongodb+srv:// URI prefix
 * See: https://docs.mongodb.com/manual/reference/connection-string/#dns-seedlist-connection-format
 */
class ExtractorClusterConnectionTest extends ExtractorTestCase
{
    /** @var Filesystem */
    private $fs;

    protected $path = '/tmp/extractor-direct';

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

    protected function getConfig()
    {
        $config = <<<JSON
{
  "parameters": {
    "db": {
      "protocol": "mongodb+srv",
      "host": "",
      "port": 27017,
      "database": "test",
      "user": "test",
      "#password": ""
    }
  }
}
JSON;
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);
    }
}
