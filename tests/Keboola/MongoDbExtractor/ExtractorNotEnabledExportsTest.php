<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Throwable;

class ExtractorNotEnabledExportsTest extends TestCase
{
    use CreateExtractorTrait;

    /** @var string */
    protected $path = '/tmp/extractor-not-enabled-exports';

    /** @var UriFactory */
    protected $uriFactory;

    /** @var ExportCommandFactory */
    protected $exportCommandFactory;

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
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);
    }

    public function testWrongConnection(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Please enable at least one export');
        $this->createExtractor($this->getConfig()['parameters'])->extract($this->path);
    }
}
