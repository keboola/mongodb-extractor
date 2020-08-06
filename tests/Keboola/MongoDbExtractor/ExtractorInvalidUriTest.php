<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\MongoDbExtractor\ExportCommandFactory;
use Keboola\MongoDbExtractor\Tests\Traits\CreateExtractorTrait;
use Keboola\MongoDbExtractor\UriFactory;
use Keboola\MongoDbExtractor\UserException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ExtractorInvalidUriTest extends TestCase
{
    use CreateExtractorTrait;

    protected string $path = '/tmp/extractor-invalid-uri';

    protected UriFactory $uriFactory;

    protected ExportCommandFactory $exportCommandFactory;

    protected function setUp(): void
    {
        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory);
    }

    protected function getConfig(): array
    {
        $config = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb://keboola-test@mongodb/test",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "enabled": true,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        },
        "mode": "mapping"
      }
    ]
  }
}
JSON;
        return (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($config, JsonEncoder::FORMAT);
    }

    public function testWrongUri(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Failed to parse MongoDB URI: ' .
            "'mongodb://mongodb://keboola-test@mongodb/test:27017/test'. Invalid host string in URI."
        );
        $this->createExtractor($this->getConfig()['parameters'])->extract($this->path);
    }
}
