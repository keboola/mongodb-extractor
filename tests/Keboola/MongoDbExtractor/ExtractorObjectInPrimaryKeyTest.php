<?php

namespace Keboola\MongoDbExtractor;

use Keboola\CsvMap\Exception\BadConfigException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ExtractorObjectInPrimaryKeyTest extends \PHPUnit\Framework\TestCase
{
    use CreateExtractorTrait;

    /** @var UriFactory */
    protected $uriFactory;

    /** @var ExportCommandFactory */
    protected $exportCommandFactory;

    /** @var Filesystem */
    private $fs;

    private $path = '/tmp/object-in-primary-key';

    protected function setUp(): void
    {
        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory);

        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);
    }

    public function testExportObjectInPrimaryKey()
    {
        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessageRegExp('~Only scalar values are allowed in primary key.~');
        $this->createExtractor($this->getConfig()['parameters'])->extract($this->path);
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
        "name": "object-in-primary-key",
        "id": 123,
        "enabled": true,
        "collection": "restaurants",
        "incremental": true,
        "mode": "mapping",
        "mapping": {
          "_id": {
            "type": "column",
            "mapping": {
              "destination": "id",
              "primaryKey": true
            }
          },
          "coord": {
            "type": "table",
            "destination": "coord",
            "tableMapping": {
              "0": "lat",
              "1": "lat"
            }
          }
        }
      }
    ]
  }
}
JSON;
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);
    }
}
