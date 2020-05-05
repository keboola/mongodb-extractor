<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ExtractorDirectConnectionWithAuthTest extends ExtractorTestCase
{
    /** @var Filesystem */
    private $fs;

    /** @var string  */
    protected $path = '/tmp/extractor-direct-auth';

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

    protected function getConfig(): array
    {
        $config = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb-auth",
      "port": 27017,
      "database": "test",
      "user": "user",
      "#password": "p#a!s@sw:o&r%^d"
    }
  }
}
JSON;
        return (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($config, JsonEncoder::FORMAT);
    }
}
