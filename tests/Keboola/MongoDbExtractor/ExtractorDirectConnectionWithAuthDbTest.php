<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ExtractorDirectConnectionWithAuthDbTest extends ExtractorTestCase
{
    /** @var Filesystem */
    private $fs;

    protected $path = '/tmp/extractor-direct-auth-db';

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
      "host": "mongodb-auth",
      "port": 27017,
      "database": "test",
      "authenticationDatabase": "authDb",
      "user": "userInAuthDb",
      "#password": "p#a!s@sw:o&r%^d"
    }
  }
}
JSON;
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);
    }
}
