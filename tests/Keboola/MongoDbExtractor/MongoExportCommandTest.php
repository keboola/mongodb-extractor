<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Filesystem\Filesystem;

class MongoExportCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    private $fs;

    private $path = '/tmp/mongoexport';

    protected function setUp()
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);
    }

    protected function tearDown()
    {
        $this->fs->remove($this->path);
    }

    public function testCreate()
    {
        $connectionParams = [
            'host' => 'localhost',
            'port' => 27017,
        ];
        $exportParams = [
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'fields' => [
                'field1',
                'field2',
            ],
            'name' => 'create-test',
        ];
        $outputPath = '/tmp';

        $command = new MongoExportCommand($connectionParams, $exportParams, $outputPath);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --collection 'myCollection' --fields 'field1,field2' --type 'csv' --out '/tmp/create-test.csv'
BASH;

        $this->assertSame($expectedCommand, $command->getCommand());
    }

    public function testCreateWithMissingRequiredParam()
    {
        $this->expectException(MongoExportCommandException::class);

        new MongoExportCommand([], [], $this->path);
    }
}
