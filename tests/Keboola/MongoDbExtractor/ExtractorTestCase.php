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

        $expectedFile = $this->path . '/' . 'export-all.json';
        $this->assertFileExists($expectedFile);

        $process = new Process('wc -l ' . $expectedFile);
        $process->mustRun();

        $this->assertSame(1, (int) $process->getOutput());
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

        $expectedJson = <<<JSON
[{"_id":{"\$oid":"5716054bee6e764c94fa7ddd"},"borough":"Bronx","cuisine":"Bakery","name":"Morris Park Bake Shop"}]\n
JSON;

        $expectedFile = $this->path . '/' . 'export-one.json';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));

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

        $expectedJson = <<<JSON
[{"_id":{"\$oid":"5716054bee6e764c94fa8c8b"},"borough":"Bronx","cuisine":"Bakery","name":"Nb. National Bakery"},{"_id":{"\$oid":"5716054cee6e764c94faba0d"},"borough":"Bronx","cuisine":"Bakery","name":"La Rosa Bakery"},{"_id":{"\$oid":"5716054cee6e764c94fad056"},"borough":"Bronx","cuisine":"Bakery","name":"Emilio Super Bakery Corp"}]\n
JSON;

        $expectedFile = $this->path . '/' . 'export-multi.json';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));
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

        $expectedJson = <<<JSON
[{"_id":{"\$oid":"5716054bee6e764c94fa8c8b"},"address":{"building":"1193","coord":[-73.9197389,40.83489170000001],"street":"Walton Avenue","zipcode":"10452"},"borough":"Bronx","cuisine":"Bakery","name":"Nb. National Bakery"},{"_id":{"\$oid":"5716054cee6e764c94faba0d"},"address":{"building":"155","coord":[-73.9147942,40.83937700000001],"street":"East 170 Street","zipcode":"10452"},"borough":"Bronx","cuisine":"Bakery","name":"La Rosa Bakery"},{"_id":{"\$oid":"5716054cee6e764c94fad056"},"address":{"building":"6A","coord":[-73.9188034,40.8381439],"street":"East Clarke Place","zipcode":"10452"},"borough":"Bronx","cuisine":"Bakery","name":"Emilio Super Bakery Corp"}]\n
JSON;

        $expectedFile = $this->path . '/' . 'export-multi-fields-paths.json';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));

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

        $expectedJson = <<<JSON
[{"_id":{"\$oid":"5716054bee6e764c94fa9620"},"address":{"building":"767","coord":[-73.86468099999999,40.865699],"street":"Allerton Avenue","zipcode":"10467"},"borough":"Bronx","name":"National Bakery"},{"_id":{"\$oid":"5716054bee6e764c94fa93f9"},"address":{"building":"944","coord":[-73.8965138,40.8212482],"street":"Intelvale Avenue","zipcode":"10459"},"borough":"Bronx","name":"National Bakery"},{"_id":{"\$oid":"5716054bee6e764c94fa8ff6"},"address":{"building":"1522-4","coord":[-73.8789604,40.8286012],"street":"Westchester Avenue","zipcode":"10472"},"borough":"Bronx","name":"National Bakery"}]\n
JSON;

        $expectedFile = $this->path . '/' . 'export-multi-with-sort-and-limit.json';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));
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

        $expectedJson = <<<JSON
[]\n
JSON;

        $expectedFile = $this->path . '/' . 'export-random-database.json';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));

    }
}
