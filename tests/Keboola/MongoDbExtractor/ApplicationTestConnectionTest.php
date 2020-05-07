<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\MongoDbExtractor\Application;
use MongoDB\Driver\Exception\AuthenticationException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ApplicationTestConnectionTest extends TestCase
{
    public function testActionTestConnectionOk(): void
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
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();

        $this->assertSame(['status' => 'ok'], $application->actionTestConnection());
    }

    public function testActionTestConnectionOkViaAuthDb(): void
    {
        $json = <<<JSON
{
  "parameters": {
    "db": {
      "host": "mongodb-auth",
      "port": 27017,
      "database": "test",
      "authenticationDatabase": "authDb",
      "user": "userInAuthDb",
      "#password": "p#a!s@sw:o&r%^d"
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
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();

        $this->assertSame(['status' => 'ok'], $application->actionTestConnection());
    }

    public function testActionTestConnectionFailWrongHost(): void
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
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();
    }

    public function testActionTestConnectionFailWithoutPassword(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('~not authorized~');

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
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();
    }

    public function testActionTestConnectionFailWrongPassword(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('~Authentication failed~');

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
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionTestConnection();
    }
}
