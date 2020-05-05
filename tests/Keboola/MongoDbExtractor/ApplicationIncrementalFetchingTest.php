<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ApplicationIncrementalFetchingTest extends TestCase
{
    /** @var Filesystem */
    private $fs;

    private $path = '/tmp/incremental-fetching-test';

    protected function setUp(): void
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path . '/out/tables');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->path);
    }

    public function testIncrementalFetchingInt()
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
        "name": "incremental",
        "id": 123,
        "collection": "incremental",
        "incremental": true,
        "incrementalFetchingColumn": "id",
        "mapping": {
          "id": "id",
          "decimal": "decimal",
          "date": "date",
          "timestamp": "timestamp"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path . '/out/tables');

        $stateFile = $this->path . '/out/state.json';
        $expectedStateFileContent = '{"lastFetchedRow":{"123":4}}';
        $incrementalFile = $this->path . '/out/tables/incremental.csv';
        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"1","123.344","2020-05-18T16:00:00Z","1587646020"
"2","133.444","2020-02-15T13:00:00Z","1587626020"
"3","783.028","2020-05-18T11:00:00Z","1587606020"
"4","283.473","2020-04-18T16:00:00Z","1587146020"

CSV;
        Assert::assertFileExists($stateFile);
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertFileExists($incrementalFile);
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));

        $this->fs->remove($incrementalFile);
        $application = new Application($config, json_decode((string) file_get_contents($stateFile), true));
        $application->actionRun($this->path . '/out/tables');

        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"4","283.473","2020-04-18T16:00:00Z","1587146020"

CSV;
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));
    }

    public function testIncrementalFetchingDecimal()
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
        "name": "incremental",
        "id": 123,
        "collection": "incremental",
        "incremental": true,
        "incrementalFetchingColumn": "decimal",
        "mapping": {
          "id": "id",
          "decimal": "decimal",
          "date": "date",
          "timestamp": "timestamp"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path . '/out/tables');

        $stateFile = $this->path . '/out/state.json';
        $expectedStateFileContent = '{"lastFetchedRow":{"123":783.028}}';
        $incrementalFile = $this->path . '/out/tables/incremental.csv';
        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"1","123.344","2020-05-18T16:00:00Z","1587646020"
"2","133.444","2020-02-15T13:00:00Z","1587626020"
"4","283.473","2020-04-18T16:00:00Z","1587146020"
"3","783.028","2020-05-18T11:00:00Z","1587606020"

CSV;
        Assert::assertFileExists($stateFile);
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertFileExists($incrementalFile);
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));

        $this->fs->remove($incrementalFile);
        $application = new Application($config, json_decode((string) file_get_contents($stateFile), true));
        $application->actionRun($this->path . '/out/tables');

        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"3","783.028","2020-05-18T11:00:00Z","1587606020"

CSV;
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));
    }

    public function testIncrementalFetchingTimestamp()
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
        "name": "incremental",
        "id": 123,
        "collection": "incremental",
        "incremental": true,
        "incrementalFetchingColumn": "timestamp",
        "mapping": {
          "id": "id",
          "decimal": "decimal",
          "date": "date",
          "timestamp": "timestamp"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path . '/out/tables');

        $stateFile = $this->path . '/out/state.json';
        $expectedStateFileContent = '{"lastFetchedRow":{"123":1587646020}}';
        $incrementalFile = $this->path . '/out/tables/incremental.csv';
        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"4","283.473","2020-04-18T16:00:00Z","1587146020"
"3","783.028","2020-05-18T11:00:00Z","1587606020"
"2","133.444","2020-02-15T13:00:00Z","1587626020"
"1","123.344","2020-05-18T16:00:00Z","1587646020"

CSV;
        Assert::assertFileExists($stateFile);
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertFileExists($incrementalFile);
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));

        $this->fs->remove($incrementalFile);
        $application = new Application($config, json_decode((string) file_get_contents($stateFile), true));
        $application->actionRun($this->path . '/out/tables');

        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"1","123.344","2020-05-18T16:00:00Z","1587646020"

CSV;
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));
    }

    public function testIncrementalFetchingLimit()
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
        "name": "incremental",
        "id": 123,
        "collection": "incremental",
        "incremental": true,
        "incrementalFetchingColumn": "id",
        "limit": "2",
        "mapping": {
          "id": "id",
          "decimal": "decimal",
          "date": "date",
          "timestamp": "timestamp"
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode(true))->decode($json, JsonEncoder::FORMAT);

        $application = new Application($config);
        $application->actionRun($this->path . '/out/tables');

        $stateFile = $this->path . '/out/state.json';
        $expectedStateFileContent = '{"lastFetchedRow":{"123":2}}';
        $incrementalFile = $this->path . '/out/tables/incremental.csv';
        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"1","123.344","2020-05-18T16:00:00Z","1587646020"
"2","133.444","2020-02-15T13:00:00Z","1587626020"

CSV;
        Assert::assertFileExists($stateFile);
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertFileExists($incrementalFile);
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));

        $this->fs->remove($incrementalFile);
        $application = new Application($config, json_decode((string) file_get_contents($stateFile), true));
        $application->actionRun($this->path . '/out/tables');

        $expectedStateFileContent = '{"lastFetchedRow":{"123":3}}';
        $expectedIncrementalFileContent = <<< CSV
"id","decimal","date","timestamp"
"2","133.444","2020-02-15T13:00:00Z","1587626020"
"3","783.028","2020-05-18T11:00:00Z","1587606020"

CSV;
        Assert::assertEquals($expectedStateFileContent, file_get_contents($stateFile));
        Assert::assertEquals($expectedIncrementalFileContent, file_get_contents($incrementalFile));
    }
}
