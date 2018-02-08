<?php

namespace Keboola\MongoDbExtractor;

use MongoDB\Driver\Exception\AuthenticationException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ApplicationTestConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testActionTestConnectionOk()
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();

        $this->assertSame(['status' => 'ok'], $application->actionTestConnection());
    }

    public function testActionTestConnectionFailWrongHost()
    {
        $this->expectException(ConnectionTimeoutException::class);

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "locahost",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();
    }

    public function testActionTestConnectionFailWithoutPassword()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('~not authorized~');

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb-auth",
      "port": 27017,
      "database": "test"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();
    }

    public function testActionTestConnectionFailWrongPassword()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageRegExp('~Authentication failed~');

        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb-auth",
      "port": 27017,
      "database": "test",
      "user": "user",
      "password": "random-password"
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();
    }
}
