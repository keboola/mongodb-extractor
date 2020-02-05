<?php

namespace Keboola\MongoDbExtractor;

use Keboola\CsvMap\Exception\BadConfigException;
use Keboola\CsvMap\Exception\BadDataException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class ExtractorTestCase extends \PHPUnit\Framework\TestCase
{
    use CreateExtractorTrait;

    /** @var string */
    protected $path;

    /** @var UriFactory */
    protected $uriFactory;

    /** @var ExportCommandFactory */
    protected $exportCommandFactory;

    protected function setUp()
    {
        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory);
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
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

        $this->assertTrue($export, 'Command successful');

        $expectedFile = $this->path . '/' . 'export-all.csv';
        $this->assertFileExists($expectedFile);

        $process = new Process('wc -l ' . $expectedFile);
        $process->mustRun();

        $this->assertSame(74, (int) $process->getOutput());
    }

    public function testExportOne()
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

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"
"5716054bee6e764c94fa7ddd","Morris Park Bake Shop"\n
CSV;
        $expectedFile = $this->path . '/' . 'export-one.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));
    }

    public function testExportOneWebalizedName()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => '_Reštaurácia s IDčkom 5716054bee6e764c94fa7ddd',
            'mapping' => $this->getMapping(),
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"
"5716054bee6e764c94fa7ddd","Morris Park Bake Shop"\n
CSV;
        $expectedFile = $this->path . '/' . 'restauracia-s-idckom-5716054bee6e764c94fa7ddd.csv';

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
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

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
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

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
            'sort' => '{"_id": 1}',
            'limit' => 3,
            'name' => 'export-multi-with-sort-and-limit',
            'mapping' => $this->getMapping(),
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"
"5716054bee6e764c94fa8ff6","National Bakery"
"5716054bee6e764c94fa93f9","National Bakery"
"5716054bee6e764c94fa9620","National Bakery"\n
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
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $extractor->extract($this->path);
    }

    public function testExportInvalidMappingBadData()
    {
        $this->expectException(BadDataException::class);
        $this->expectExceptionMessage('Error writing \'id\' column: Cannot write object into a column');

        $exportParams = [
            'collection' => 'restaurants',
            'name' => 'export-bad-mapping',
            'mapping' => [
                '_id' => 'id' // _id is object
            ],
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $extractor->extract($this->path);
    }

    public function testExportInvalidMappingBadConfig()
    {
        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage('Key \'mapping.destination\' is not set for column \'2\'');

        $exportParams = [
            'collection' => 'restaurants',
            'name' => 'export-bad-mapping',
            'mapping' => [
                '2' => []
            ],
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $extractor->extract($this->path);
    }

    public function testExportRandomCollection()
    {
        $exportParams = [
            'collection' => 'randomCollection',
            'query' => '{_id: ObjectId("5716054bee6e764c94fa7ddd")}',
            'name' => 'export-random-database',
            'mapping' => $this->getMapping(),
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id","name"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-random-database.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));
    }

    public function testExportRelatedTableFirstItemEmpty()
    {
        $exportParams = [
            'collection' => 'restaurants',
            'query' => <<<JSON
{
  "_id": {
    "\$in":[
      {"\$oid":"5716054cee6e764c94fadb21"},
      {"\$oid":"5716054cee6e764c94fadb22"},
      {"\$oid":"5716054cee6e764c94fadb23"}
    ]
  }
}
JSON
            ,
            'sort' => '{"_id": -1}',
            'limit' => 3,
            'name' => 'export-related-table-first-item-empty',
            'mapping' => [
                '_id.$oid' => [
                    'type' => 'column',
                    'mapping' => [
                        'destination' => 'id',
                        'primaryKey' => true
                    ]
                ],
                'coords' => [
                    'type' => 'table',
                    'destination' => 'export-related-table-first-item-empty-coord',
                    'tableMapping' => [
                        'w' => 'w',
                        'n' => 'n'
                    ]
                ],
            ],
            'mode' => 'mapping',
            'enabled' => true,
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $export = $extractor->extract($this->path);

        $this->assertTrue($export, 'Command successful');

        $expectedJson = <<<CSV
"id"
"5716054cee6e764c94fadb23"
"5716054cee6e764c94fadb22"
"5716054cee6e764c94fadb21"\n
CSV;

        $expectedFile = $this->path . '/' . 'export-related-table-first-item-empty.csv';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedFile));


        $expectedJsonCoord = <<<CSV
"w","n","export-related-table-first-item-empty_pk"
"","","5716054cee6e764c94fadb23"
"-73.887492","40.8556246","5716054cee6e764c94fadb21"\n
CSV;

        $expectedFileCoord = $this->path . '/' . 'export-related-table-first-item-empty-coord.csv';

        $this->assertFileExists($expectedFileCoord);
        $this->assertEquals($expectedJsonCoord, file_get_contents($expectedFileCoord));
    }
}
