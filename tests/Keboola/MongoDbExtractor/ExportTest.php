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

    public function testCreateManifestDefault()
    {
        $yaml = <<<YAML
parameters:
  db:
    host: 127.0.0.1
    port: 27017
    database: test
  exports:
    - name: create-manifest-default
      collection: 'test'
      fields:
        - name
YAML;

        $config = Yaml::parse($yaml);

        $export = new Export(
            $config['parameters']['db'],
            $config['parameters']['exports'][0],
            $this->path,
            $config['parameters']['exports'][0]['name']
        );

        $export->createManifest();

        $expectedYaml = <<<YAML
incremental: true\n
YAML;

        $expectedFile = $this->path . '/create-manifest-default.json.manifest';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedYaml, file_get_contents($expectedFile));
    }

    public function testCreateManifestFull()
    {
        $yaml = <<<YAML
parameters:
  db:
    host: 127.0.0.1
    port: 27017
    database: test
  exports:
    - name: create-manifest-full
      collection: 'test'
      fields:
        - _id
        - name
      incremental: false
      primaryKey:
        - _id
YAML;

        $config = Yaml::parse($yaml);

        $export = new Export(
            $config['parameters']['db'],
            $config['parameters']['exports'][0],
            $this->path,
            $config['parameters']['exports'][0]['name']
        );

        $export->createManifest();

        $expectedYaml = <<<YAML
incremental: false
primary_key:
    - _id\n
YAML;

        $expectedFile = $this->path . '/create-manifest-full.json.manifest';

        $this->assertFileExists($expectedFile);
        $this->assertEquals($expectedYaml, file_get_contents($expectedFile));
    }
}
