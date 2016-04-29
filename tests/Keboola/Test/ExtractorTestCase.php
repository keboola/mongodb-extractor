<?php

namespace Keboola\Test;

use Keboola\MongoDbExtractor\Export;
use Keboola\MongoDbExtractor\Extractor;
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

    public function testExportAll()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'fields' => [
                'borough',
                'cuisine',
                'name',
            ],
            'name' => 'export-all',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
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
            'fields' => [
                'borough',
                'cuisine',
                'name',
            ],
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => 'export-one',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name
Bronx,Bakery,Morris Park Bake Shop\n
CSV;

        $expectedFile = $this->path . '/' . 'export-one.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));

    }

    public function testExportMulti()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'fields' => [
                'borough',
                'cuisine',
                'name',
            ],
            'query' => '{borough : "Bronx", cuisine: "Bakery", "address.zipcode": "10452"}',
            'name' => 'export-multi',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name
Bronx,Bakery,Nb. National Bakery
Bronx,Bakery,La Rosa Bakery
Bronx,Bakery,Emilio Super Bakery Corp\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));
    }

    public function testExportMultiFieldsPaths()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'fields' => [
                'borough',
                'cuisine',
                'name',
                'address.street',
                'address.zipcode',
                'address.building',
            ],
            'query' => '{borough : "Bronx", cuisine: "Bakery", "address.zipcode": "10452"}',
            'name' => 'export-multi-fields-paths',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name,address.street,address.zipcode,address.building
Bronx,Bakery,Nb. National Bakery,Walton Avenue,10452,1193
Bronx,Bakery,La Rosa Bakery,East 170 Street,10452,155
Bronx,Bakery,Emilio Super Bakery Corp,East Clarke Place,10452,6A\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-fields-paths.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));

    }

    public function testExportMultiWithJson()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'fields' => [
                'borough',
                'cuisine',
                'name',
                'address', // as JSON string
            ],
            'query' => '{borough : "Bronx", cuisine: "Bakery", "address.zipcode": "10452"}',
            'name' => 'export-multi-with-json',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name,address
Bronx,Bakery,Nb. National Bakery,"{""building"":""1193"",""coord"":[-73.9197389,40.83489170000001],""street"":""Walton Avenue"",""zipcode"":""10452""}"
Bronx,Bakery,La Rosa Bakery,"{""building"":""155"",""coord"":[-73.9147942,40.83937700000001],""street"":""East 170 Street"",""zipcode"":""10452""}"
Bronx,Bakery,Emilio Super Bakery Corp,"{""building"":""6A"",""coord"":[-73.9188034,40.8381439],""street"":""East Clarke Place"",""zipcode"":""10452""}"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-with-json.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));
    }

    public function testExportMultiWithSortAndLimit()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'fields' => [
                'name',
                'borough',
                'address.street',
                'address.zipcode',
            ],
            'query' => '{name: "National Bakery"}',
            'sort' => '{"address.street": 1}',
            'limit' => 3,
            'name' => 'export-multi-with-sort-and-limit',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedCsv = <<<CSV
name,borough,address.street,address.zipcode
National Bakery,Bronx,Allerton Avenue,10467
National Bakery,Bronx,Intelvale Avenue,10459
National Bakery,Bronx,Westchester Avenue,10472\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-with-sort-and-limit.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));
    }

    public function testExportBadQueryJson()
    {
        $this->expectException(ProcessFailedException::class);

        $exportParams = [
            'collection' => 'restaurants',
            'fields' => [
                'borough',
                'cuisine',
                'name',
            ],
            'query' => '{a: b}', // invalid JSON
            'name' => 'export-bad-query',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
        ]);
    }

    public function testExportRandomCollection()
    {
        $exportParams = [
            'collection' => 'randomCollection',
            'fields' => [
                'borough',
                'cuisine',
                'name',
            ],
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => 'export-random-database',
        ];

        $extractor = new Extractor($this->getConfig()['parameters'], $this->logger);
        $export = $extractor->export([
            new Export($this->getConfig()['parameters']['db'], $exportParams, $this->path, $exportParams['name']),
        ]);

        $this->assertTrue($export, 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name\n
CSV;

        $expectedFile = $this->path . '/' . 'export-random-database.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));

    }
}
