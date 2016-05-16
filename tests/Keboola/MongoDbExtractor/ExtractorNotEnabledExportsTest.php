<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Yaml\Yaml;
use Keboola\DbExtractor\Logger;
use Keboola\DbExtractor\Exception\UserException;

class ExtractorNotEnabledExportsTes extends \PHPUnit_Framework_TestCase
{
    private $logger;

    protected $path = '/tmp/extractor-not-enabled-exports';

    protected function setUp()
    {
        $this->logger = new Logger('keboola.ex-mongodb');
    }

    protected function getConfig()
    {
        $config = <<<YAML
parameters:
  db:
    host: mongodb
    port: 27017
    database: test
  exports:
    - name: bakeries
      id: 123
      enabled: false
      collection: restaurants
      incremental: true
      mapping:
        '_id.\$oid': id
YAML;
        return Yaml::parse($config);

    }

    public function testWrongConnection()
    {
        $this->expectException(\Exception::class);

        $exportParams = $this->getConfig()['parameters']['exports'][0];

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
}
