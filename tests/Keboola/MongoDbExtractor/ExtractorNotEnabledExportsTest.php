<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\MongoDbExtractor\ExportCommandFactory;
use Keboola\MongoDbExtractor\Tests\Traits\CreateExtractorTrait;
use Keboola\MongoDbExtractor\UriFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Throwable;

class ExtractorNotEnabledExportsTest extends TestCase
{
    use CreateExtractorTrait;

    protected string $path = '/tmp/extractor-not-enabled-exports';

    protected UriFactory $uriFactory;

    protected ExportCommandFactory $exportCommandFactory;

    protected function setUp(): void
    {
        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory, false);
    }

    protected function getConfig(): array
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
        },
        "mode": "mapping"
      }
    ]
  }
}
JSON;
        return (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($config, JsonEncoder::FORMAT);
    }

    public function testWrongConnection(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Please enable at least one export');
        $this->createExtractor($this->getConfig()['parameters'])->extract($this->path);
    }
}
