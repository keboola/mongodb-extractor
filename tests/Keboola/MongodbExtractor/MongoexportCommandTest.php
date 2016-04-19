<?php

namespace Keboola\MongodbExtractor;

use Symfony\Component\Filesystem\Filesystem;

class MongoexportCommandTest extends \PHPUnit_Framework_TestCase
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

        $command = new MongoexportCommand($connectionParams, $exportParams, $outputPath);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --collection 'myCollection' --fields 'field1,field2' --csv --out '/tmp/create-test.csv'
BASH;

        $this->assertSame($expectedCommand, $command->getCommand());
    }

    public function testExportOne()
    {
        $connectionParams = [
            'host' => 'mongodb',
            'port' => 27017,
        ];
        $exportParams = [
            'db' => 'test',
            'collection' => 'restaurants',
            'fields' => [
                'borough',
                'cuisine',
                'name',
            ],
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => 'export-one',
        ];

        $command = new MongoexportCommand($connectionParams, $exportParams, $this->path);
        $this->assertTrue($command->run(), 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name
"Bronx","Bakery","Morris Park Bake Shop"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-one.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));

    }

    public function testExportMulti()
    {
        $connectionParams = [
            'host' => 'mongodb',
            'port' => 27017,
        ];
        $exportParams = [
            'db' => 'test',
            'collection' => 'restaurants',
            'fields' => [
                'borough',
                'cuisine',
                'name',
            ],
            'query' => '{borough : "Bronx", cuisine: "Bakery", "address.zipcode": "10452"}',
            'name' => 'export-multi',
        ];

        $command = new MongoexportCommand($connectionParams, $exportParams, $this->path);
        $this->assertTrue($command->run(), 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name
"Bronx","Bakery","Nb. National Bakery"
"Bronx","Bakery","La Rosa Bakery"
"Bronx","Bakery","Emilio Super Bakery Corp"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));
    }

    public function testExportMultiFieldsPaths()
    {
        $connectionParams = [
            'host' => 'mongodb',
            'port' => 27017,
        ];
        $exportParams = [
            'db' => 'test',
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

        $command = new MongoexportCommand($connectionParams, $exportParams, $this->path);
        $this->assertTrue($command->run(), 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name,address.street,address.zipcode,address.building
"Bronx","Bakery","Nb. National Bakery","Walton Avenue","10452","1193"
"Bronx","Bakery","La Rosa Bakery","East 170 Street","10452","155"
"Bronx","Bakery","Emilio Super Bakery Corp","East Clarke Place","10452","6A"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-fields-paths.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));

    }

    public function testExportMultiWithJson()
    {
        $connectionParams = [
            'host' => 'mongodb',
            'port' => 27017,
        ];
        $exportParams = [
            'db' => 'test',
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

        $command = new MongoexportCommand($connectionParams, $exportParams, $this->path);
        $this->assertTrue($command->run(), 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name,address
"Bronx","Bakery","Nb. National Bakery","{ ""building"" : ""1193"", ""coord"" : [ -73.9197389, 40.83489170000001 ], ""street"" : ""Walton Avenue"", ""zipcode"" : ""10452"" }"
"Bronx","Bakery","La Rosa Bakery","{ ""building"" : ""155"", ""coord"" : [ -73.9147942, 40.83937700000001 ], ""street"" : ""East 170 Street"", ""zipcode"" : ""10452"" }"
"Bronx","Bakery","Emilio Super Bakery Corp","{ ""building"" : ""6A"", ""coord"" : [ -73.9188034, 40.8381439 ], ""street"" : ""East Clarke Place"", ""zipcode"" : ""10452"" }"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-with-json.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));
    }

    public function testExportMultiWithJsonFail()
    {
        $connectionParams = [
            'host' => 'mongodb',
            'port' => 27017,
        ];
        $exportParams = [
            'db' => 'test',
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

        $command = new MongoexportCommand($connectionParams, $exportParams, $this->path);
        $this->assertTrue($command->run(), 'Command successful');

        $expectedCsv = <<<CSV
borough,cuisine,name,address
"Bronx","Bakery","Nb. National Bakery","{ ""building"" : ""1193"", ""coord"" : [ -73.9197389, 40.83489170000001 ], ""street"" : ""Walton Avenue"", ""zipcode"" : ""10452"" }"
"Bronx","Bakery","La Rosa Bakery","{ ""building"" : ""155"", ""coord"" : [ -73.9147942, 40.83937700000001 ], ""street"" : ""East 170 Street"", ""zipcode"" : ""10452"" }"
"Bronx","Bakery","Emilio Super Bakery Corp","{ ""building"" : ""6A"", ""coord"" : [ -73.9188034, 40.8381439 ], ""street"" : ""East Clarke Place"", ""zipcode"" : ""10452"" }"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-multi-with-json.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedCsv, file_get_contents($expectedFile));
    }
}
