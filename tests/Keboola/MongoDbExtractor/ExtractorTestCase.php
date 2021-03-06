<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\CsvMap\Exception\BadConfigException;
use Keboola\CsvMap\Exception\BadDataException;
use Keboola\MongoDbExtractor\ExportCommandFactory;
use Keboola\MongoDbExtractor\Tests\Traits\CreateExtractorTrait;
use Keboola\MongoDbExtractor\UriFactory;
use Keboola\MongoDbExtractor\UserException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class ExtractorTestCase extends TestCase
{
    use CreateExtractorTrait;

    protected string $path;

    protected UriFactory $uriFactory;

    protected ExportCommandFactory $exportCommandFactory;

    protected function setUp(): void
    {
        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory, false);
    }

    abstract protected function getConfig(): array;

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

    public function testExportAll(): void
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

        $process = Process::fromShellCommandline('wc -l ' . $expectedFile);
        $process->mustRun();

        $this->assertSame(74, (int) $process->getOutput());
    }

    public function testArrayInHeader(): void
    {
        $exportParams = [
            'collection' => 'restaurants',
            'name' => 'export-all',
            'mapping' => [
                'name' => [
                    'type' => 'column',
                    'mapping' => [
                        'destination' => ['bad', 'type'],
                    ],
                ],
            ],
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'CSV writing error. Header and mapped documents must be scalar values. Cannot write array into a column'
        );
        $extractor->extract($this->path);
    }

    public function testExportOne(): void
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

    public function testExportOneWebalizedName(): void
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

    public function testExportMulti(): void
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

    public function testExportMultiFieldsPaths(): void
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

    public function testExportMultiWithSortAndLimit(): void
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

    public function testExportBadQueryJson(): void
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

    public function testExportInvalidMappingBadData(): void
    {
        $this->expectException(BadDataException::class);
        $this->expectExceptionMessage('Error writing \'id\' column: Cannot write object into a column');

        $exportParams = [
            'collection' => 'restaurants',
            'name' => 'export-bad-mapping',
            'mapping' => [
                '_id' => 'id', // _id is object
            ],
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $extractor->extract($this->path);
    }

    public function testExportInvalidMappingBadConfig(): void
    {
        $this->expectException(BadConfigException::class);
        $this->expectExceptionMessage('Key \'mapping.destination\' is not set for column \'2\'');

        $exportParams = [
            'collection' => 'restaurants',
            'name' => 'export-bad-mapping',
            'mapping' => [
                '2' => [],
            ],
            'enabled' => true,
            'mode' => 'mapping',
        ];

        $parameters = $this->getConfig()['parameters'];
        $parameters['exports'][] = $exportParams;

        $extractor = $this->createExtractor($parameters);
        $extractor->extract($this->path);
    }

    public function testExportRandomCollection(): void
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

    public function testExportRelatedTableFirstItemEmpty(): void
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
                        'primaryKey' => true,
                    ],
                ],
                'coords' => [
                    'type' => 'table',
                    'destination' => 'export-related-table-first-item-empty-coord',
                    'tableMapping' => [
                        'w' => 'w',
                        'n' => 'n',
                    ],
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
