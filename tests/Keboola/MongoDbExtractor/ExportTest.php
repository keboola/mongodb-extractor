<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;

class ExportTest extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    private $fs;

    protected $path = '/tmp/export';

    protected function setUp()
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->fs->remove($this->path);
    }

    public function testExportFull()
    {
        $yaml = <<<YAML
parameters:
  db:
    host: mongodb
    port: 27017
    database: test
  exports:
    - name: bakeries
      collection: restaurants
      fields:
        - _id
        - name
        - address
      query: '{borough: "Bronx", "address.street": "Westchester Avenue"}'
      mapping:
        '_id.\$oid':
          type: column
          mapping:
            destination: id
            primaryKey: true
        name:
          type: column
          mapping:
            destination: name
        address:
          type: table
          destination: bakeries-coords
          parentKey:
            destination: bakeries_id
          tableMapping:
            coord.0:
              type: column
              mapping:
                destination: w
            coord.1:
              type: column
              mapping:
                destination: n
            zipcode:
              type: column
              mapping:
                destination: zipcode
                primaryKey: true
            street:
              type: column
              mapping:
                destination: street
                primaryKey: true
YAML;

        $config = Yaml::parse($yaml);

        $export = new Export(
            $config['parameters']['db'],
            $config['parameters']['exports'][0],
            $this->path,
            $config['parameters']['exports'][0]['name'],
            $config['parameters']['exports'][0]['mapping']
        );

        $export->export();

        // json
        $expectedJsonFile = $this->path . '/bakeries.json';
        $expectedJson = <<<JSON
[{"_id":{"\$oid":"5716054bee6e764c94fa841e"},"address":{"building":"1484","coord":[-73.8806669,40.8283447],"street":"Westchester Avenue","zipcode":"10472"},"name":"Nacional Bakery #1"},{"_id":{"\$oid":"5716054bee6e764c94fa8c13"},"address":{"building":"104512","coord":[-73.88541699999999,40.82766],"street":"Westchester Avenue","zipcode":"10459"},"name":"La Nueva Giralda Bakery"},{"_id":{"\$oid":"5716054bee6e764c94fa8ff6"},"address":{"building":"1522-4","coord":[-73.8789604,40.8286012],"street":"Westchester Avenue","zipcode":"10472"},"name":"National Bakery"},{"_id":{"\$oid":"5716054cee6e764c94fa9f31"},"address":{"building":"1617","coord":[-73.8747516,40.829474],"street":"Westchester Avenue","zipcode":"10472"},"name":"1617-A National Bakery"},{"_id":{"\$oid":"5716054cee6e764c94faa105"},"address":{"building":"2214","coord":[-73.8510158,40.8342588],"street":"Westchester Avenue","zipcode":"10462"},"name":"National Bakery"},{"_id":{"\$oid":"5716054cee6e764c94fad66f"},"address":{"building":"1579","coord":[-73.87670779999999,40.8290734],"street":"Westchester Avenue","zipcode":"10472"},"name":"Jacqueline'S Bakery Restaurant"}]\n
JSON;
        $this->assertFileExists($expectedJsonFile);
        $this->assertEquals($expectedJson, file_get_contents($expectedJsonFile));

        $export->parseAndCreateManifest();

        // main csv
        $expectedCsvFileMain = $this->path . '/bakeries.csv';
        $expectedCsvMain = <<<CSV
"id","name"
"5716054bee6e764c94fa841e","Nacional Bakery #1"
"5716054bee6e764c94fa8c13","La Nueva Giralda Bakery"
"5716054bee6e764c94fa8ff6","National Bakery"
"5716054cee6e764c94fa9f31","1617-A National Bakery"
"5716054cee6e764c94faa105","National Bakery"
"5716054cee6e764c94fad66f","Jacqueline'S Bakery Restaurant"\n
CSV;
        $this->assertFileExists($expectedCsvFileMain);
        $this->assertEquals($expectedCsvMain, file_get_contents($expectedCsvFileMain));

        // main manifest
        $expectedYamlFileMain = $this->path . '/bakeries.csv.manifest';
        $expectedYamlMain = <<<YAML
primary_key:
    - id
incremental: false\n
YAML;
        $this->assertFileExists($expectedYamlFileMain);
        $this->assertEquals($expectedYamlMain, file_get_contents($expectedYamlFileMain));

        // related csv
        $expectedCsvFileRelated = $this->path . '/bakeries-coords.csv';
        $expectedCsvRelated = <<<CSV
"w","n","zipcode","street","bakeries_id"
"-73.8806669","40.8283447","10472","Westchester Avenue","5716054bee6e764c94fa841e"
"-73.885417","40.82766","10459","Westchester Avenue","5716054bee6e764c94fa8c13"
"-73.8789604","40.8286012","10472","Westchester Avenue","5716054bee6e764c94fa8ff6"
"-73.8747516","40.829474","10472","Westchester Avenue","5716054cee6e764c94fa9f31"
"-73.8510158","40.8342588","10462","Westchester Avenue","5716054cee6e764c94faa105"
"-73.8767078","40.8290734","10472","Westchester Avenue","5716054cee6e764c94fad66f"\n
CSV;
        $this->assertFileExists($expectedCsvFileRelated);
        $this->assertEquals($expectedCsvRelated, file_get_contents($expectedCsvFileRelated));

        // related manifest
        $expectedYamlFileRelated = $this->path . '/bakeries-coords.csv.manifest';
        $expectedYamlRelated = <<<YAML
primary_key:
    - zipcode
    - street
incremental: false\n
YAML;
        $this->assertFileExists($expectedYamlFileRelated);
        $this->assertEquals($expectedYamlRelated, file_get_contents($expectedYamlFileRelated));
    }
}
