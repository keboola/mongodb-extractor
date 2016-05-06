<?php

namespace Keboola\MongoDbExtractor;

use Keboola\DbExtractor\Logger;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class ExtractorTestCase extends \PHPUnit_Framework_TestCase
{
    protected $path;

    protected $logger;

    protected function setUp()
    {
        $this->logger = new Logger('keboola.ex-mongodb');
    }

    abstract protected function getConfig();

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

    public function testExportAll()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'name' => 'export-all',
            'mapping' => $this->getMapping(),
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export(
                $this->getConfig()['parameters']['db'],
                $exportParams,
                $this->path,
                $exportParams['name'],
                $exportParams['mapping']
            ),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedFile = $this->path . '/' . 'export-all.csv';
        $this->assertFileExists($expectedFile);

        $process = new Process('wc -l ' . $expectedFile);
        $process->mustRun();

        $this->assertSame(72, (int) $process->getOutput());
    }

    public function testExportOne()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => 'export-one',
            'mapping' => $this->getMapping(),
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export(
                $this->getConfig()['parameters']['db'],
                $exportParams,
                $this->path,
                $exportParams['name'],
                $exportParams['mapping']
            ),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"
"5716054bee6e764c94fa7ddd","Morris Park Bake Shop"\n
CSV;
        $expectedFile = $this->path . '/' . 'export-one.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));

    }

    public function testExportMulti()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'query' => '{borough : "Bronx", cuisine: "Bakery", "address.zipcode": "10452"}',
            'name' => 'export-multi',
            'mapping' => $this->getMapping(),
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export(
                $this->getConfig()['parameters']['db'],
                $exportParams,
                $this->path,
                $exportParams['name'],
                $exportParams['mapping']
            ),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"
"5716054bee6e764c94fa8c8b","Nb. National Bakery"
"5716054cee6e764c94faba0d","La Rosa Bakery"
"5716054cee6e764c94fad056","Emilio Super Bakery Corp"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));
    }

    public function testExportMultiFieldsPaths()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'query' => '{borough : "Bronx", cuisine: "Bakery", "address.zipcode": "10452"}',
            'name' => 'export-multi-fields-paths',
            'mapping' => $this->getMapping(),
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export(
                $this->getConfig()['parameters']['db'],
                $exportParams,
                $this->path,
                $exportParams['name'],
                $exportParams['mapping']
            ),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"
"5716054bee6e764c94fa8c8b","Nb. National Bakery"
"5716054cee6e764c94faba0d","La Rosa Bakery"
"5716054cee6e764c94fad056","Emilio Super Bakery Corp"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-fields-paths.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));

    }

    public function testExportMultiWithSortAndLimit()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'query' => '{name: "National Bakery"}',
            'sort' => '{"address.street": 1}',
            'limit' => 3,
            'name' => 'export-multi-with-sort-and-limit',
            'mapping' => $this->getMapping(),
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export(
                $this->getConfig()['parameters']['db'],
                $exportParams,
                $this->path,
                $exportParams['name'],
                $exportParams['mapping']
            ),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"
"5716054bee6e764c94fa9620","National Bakery"
"5716054bee6e764c94fa93f9","National Bakery"
"5716054bee6e764c94fa8ff6","National Bakery"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-with-sort-and-limit.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));
    }

    public function testExportBadQueryJson()
    {
        $this->expectException(ProcessFailedException::class);

        $exportParams = [
            'collection' => 'restaurants',
            'query' => '{a: b}', // invalid JSON
            'name' => 'export-bad-query',
            'mapping' => $this->getMapping(),
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $extractor->export([
            new Export(
                $this->getConfig()['parameters']['db'],
                $exportParams,
                $this->path,
                $exportParams['name'],
                $exportParams['mapping']
            ),
        ]);
    }

    public function testExportRandomCollection()
    {
        $exportParams = [
            'collection' => 'randomCollection',
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => 'export-random-database',
            'mapping' => $this->getMapping(),
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export(
                $this->getConfig()['parameters']['db'],
                $exportParams,
                $this->path,
                $exportParams['name'],
                $exportParams['mapping']
            ),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-random-database.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));

    }
}
