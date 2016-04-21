<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Yaml\Yaml;
use Keboola\DbExtractor\Logger;
use Keboola\DbExtractor\Exception\UserException;

class ExtractorWrongConnectionTest extends \PHPUnit_Framework_TestCase
{
    private $logger;

    protected function setUp()
    {
        $this->logger = new Logger('keboola.ex-mongodb');
    }

    protected function getConfig()
    {
        $config = <<<YAML
parameters:
  db:
    host: localhost
    port: 12345
YAML;
        return Yaml::parse($config);

    }

    public function testWrongConnection()
    {
        $this->expectException(UserException::class);

        new Extractor($this->getConfig(), $this->logger);
    }
}
