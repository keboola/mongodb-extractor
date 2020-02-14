<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ExtractorNoSpaceLeftOnDeviceTest extends \PHPUnit\Framework\TestCase
{
    use CreateExtractorTrait;

    /** @var UriFactory */
    protected $uriFactory;

    /** @var ExportCommandFactory */
    protected $exportCommandFactory;

    /** @var Filesystem */
    private $fs;

    /** @var string */
    private $path = '/tmp/no-space-left-on-device';

    /** @var string */
    private $file = 'export-one.csv';

    protected function setUp()
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        // simulate full disk
        $process = new Process('ln -s /dev/full ' . $this->path . '/' . $this->file);
        $process->mustRun();

        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory);
    }

    public function testExportNoSpaceLeftOnDevice()
    {
        $this->expectException(\Exception::class);

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
        $this->createExtractor($parameters)->extract($this->path);
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
    }
  }
}
JSON;
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);
    }

    private function getMapping()
    {
        return [
            '_id.$oid' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'id',
                ]
            ],
            'name' => [
                'type' => 'column',
                'mapping' => [
                    'destination' => 'name'
                ]
            ],
        ];
    }
}
