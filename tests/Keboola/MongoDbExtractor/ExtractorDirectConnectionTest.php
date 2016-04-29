<?php

namespace Keboola\MongoDbExtractor;

use Keboola\Test\ExtractorTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ExtractorDirectConnectionTest extends ExtractorTestCase
{
    /** @var Filesystem */
    private $fs;

    protected $path = '/tmp/extractor-direct';

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

    protected function getConfig()
    {
        $config = <<<YAML
parameters:
  db:
    host: mongodb
    port: 27017
    database: test
YAML;
        return Yaml::parse($config);

    }
}
