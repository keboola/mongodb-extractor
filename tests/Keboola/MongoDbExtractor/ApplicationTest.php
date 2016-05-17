<?php

namespace Keboola\MongoDbExtractor;

use Symfony\Component\Yaml\Yaml;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
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
      collection: restaurants
      incremental: true
      mapping:
        '_id.\$oid': id
YAML;
        return Yaml::parse($config);

    }

    public function testActionTestConnectionOk()
    {
        $application = new Application($this->getConfig());
        $application->actionTestConnection();

        $this->assertSame(['status' => 'ok'], $application->actionTestConnection());
    }

    public function testActionTestConnectionFail()
    {
        $this->expectException(\MongoDB\Driver\Exception\ConnectionTimeoutException::class);

        $config = $this->getConfig();
        $config['parameters']['db']['host'] = 'locahost';

        $application = new Application($config);
        $application->actionTestConnection();
    }
}
