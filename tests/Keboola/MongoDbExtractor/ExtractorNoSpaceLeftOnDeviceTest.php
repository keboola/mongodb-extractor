<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Throwable;

class ExtractorNoSpaceLeftOnDeviceTest extends TestCase
{
    use CreateExtractorTrait;

    /** @var UriFactory */
    protected $uriFactory;

    /** @var ExportCommandFactory */
    protected $exportCommandFactory;

    /** @var string */
    private $path = '/tmp/no-space-left-on-device';

    /** @var string */
    private $file = 'export-one.csv';

    protected function setUp(): void
    {
        $fs = new Filesystem;
        $fs->remove($this->path);
        $fs->mkdir($this->path);

        // simulate full disk
        $process = new Process('ln -s /dev/full ' . $this->path . '/' . $this->file);
        $process->mustRun();

        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory);
    }

    public function testExportNoSpaceLeftOnDevice(): void
    {

        $exportParams = [
            'collection' => 'restaurants',
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => 'export-one',
            'mapping' => $this->getMapping(),
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;
        $this->expectException(Throwable::class);
        $this->createExtractor($parameters)->extract($this->path);
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
    }
  }
}
JSON;
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);
    }

    private function getMapping(): array
    {
        return [
            '_id.$oid' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'id',
                ],
            ],
            'name' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'name',
                ],
            ],
        ];
    }
}
