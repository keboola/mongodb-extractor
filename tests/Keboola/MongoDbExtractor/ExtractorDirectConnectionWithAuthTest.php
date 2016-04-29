<?php

namespace Keboola\MongoDbExtractor;

use Keboola\Test\ExtractorTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ExtractorDirectConnectionWithAuthTest extends ExtractorTestCase
{
    /** @var Filesystem */
    private $fs;

    protected $path = '/tmp/extractor-direct-auth';

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
    host: mongodb-auth
    port: 27017
    database: test
    user: user
    '#password': user
YAML;
        return Yaml::parse($config);

    }
}
