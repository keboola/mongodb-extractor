<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\MongoDbExtractor\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * PKs were initially generated only based on the content of the document.
 * This resulted in duplicate PKs of sub-documents with same content, but coming from a different parent document.
 * Later, this behavior was corrected and the parents are taken into account when generating the PK,
 * when "includeParentInPK" = true in config.
 */
class ApplicationPrimaryKeysTest extends TestCase
{
    private Filesystem $fs;

    protected string $path = '/tmp/application-test';

    protected function setUp(): void
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->path);
    }

    public function testLegacyBehaviour(): void
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
        "name": "root",
        "id": 123,
        "collection": "sameSubDocs",
        "mapping": {
          "_id.\$oid": "id",
          "item": {
            "type": "table",
            "destination": "level1",
            "tableMapping": {
              "a.itemId": "a",
              "b.itemId": "b",
              "a": {
                "type": "table",
                "destination": "level2-a",
                "tableMapping": {
                  "count": "count",
                  "itemId": "itemId"
                }
              },
              "b": {
                "type": "table",
                "destination": "level2-b",
                "tableMapping": {
                  "count": "count",
                  "itemId": "itemId"
                }
              }
            }
          }
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        $application = new Application($config);
        $application->actionRun($this->path);

        // Root level
        $rootPath = $this->path . '/root.csv';
        $this->assertFileExists($rootPath);
        $rootItems = file_get_contents($rootPath);
        $expectedRootItems = <<<END
"id","level1"
"5716054bee6e764c94fa7aaa","7d0cae8a5690da82b226698d23e94a21"
"5716054bee6e764c94fa7aab","dc6b61323e318be5ec4a277583bdfe0b"
"5716054bee6e764c94fa7aac","81f804cf4fb0e85c4a6563ce5184c92d"
"5716054bee6e764c94fa7aad","0e758a1da8bc9357e64286b1d0fbe2a0"

END;
        $this->assertSame($expectedRootItems, $rootItems);

        // Level 1 - SAME content -> SAME keys!!!
        $level1Path = $this->path . '/level1.csv';
        $this->assertFileExists($level1Path);
        $level1Items = file_get_contents($level1Path);
        $expectedLevel1Items = <<<END
"a","b","level2-a","level2-b","root_pk"
"123","123","d65f09a7f75f31ad639f3c2b1d7d4d3d","d65f09a7f75f31ad639f3c2b1d7d4d3d","7d0cae8a5690da82b226698d23e94a21"
"123","123","d65f09a7f75f31ad639f3c2b1d7d4d3d","d65f09a7f75f31ad639f3c2b1d7d4d3d","dc6b61323e318be5ec4a277583bdfe0b"
"456","123","77297cc0f4e8f0ea5641dd4b688b331f","77297cc0f4e8f0ea5641dd4b688b331f","81f804cf4fb0e85c4a6563ce5184c92d"
"456","123","77297cc0f4e8f0ea5641dd4b688b331f","77297cc0f4e8f0ea5641dd4b688b331f","0e758a1da8bc9357e64286b1d0fbe2a0"

END;
        $this->assertSame($expectedLevel1Items, $level1Items);

        // Level 2-A
        $level2APath = $this->path . '/level2-a.csv';
        $this->assertFileExists($level2APath);
        $level2AItems = file_get_contents($level2APath);
        $expectedLevel2AItems = <<<END
"count","itemId","level1_pk"
"1","123","d65f09a7f75f31ad639f3c2b1d7d4d3d"
"1","123","d65f09a7f75f31ad639f3c2b1d7d4d3d"
"20","456","77297cc0f4e8f0ea5641dd4b688b331f"
"20","456","77297cc0f4e8f0ea5641dd4b688b331f"

END;
        $this->assertSame($expectedLevel2AItems, $level2AItems);

        // Level 2-B
        $level2BPath = $this->path . '/level2-b.csv';
        $this->assertFileExists($level2APath);
        $level2BItems = file_get_contents($level2BPath);
        $expectedLevel2BItems = <<<END
"count","itemId","level1_pk"
"1","123","d65f09a7f75f31ad639f3c2b1d7d4d3d"
"1","123","d65f09a7f75f31ad639f3c2b1d7d4d3d"
"1","123","77297cc0f4e8f0ea5641dd4b688b331f"
"1","123","77297cc0f4e8f0ea5641dd4b688b331f"

END;
        $this->assertSame($expectedLevel2BItems, $level2BItems);
    }

    public function testIncludeParentInPK(): void
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
        "name": "root",
        "id": 123,
        "collection": "sameSubDocs",
        "includeParentInPK": true,
        "mapping": {
          "_id.\$oid": "id",
          "item": {
            "type": "table",
            "destination": "level1",
            "tableMapping": {
              "a.itemId": "a",
              "b.itemId": "b",
              "a": {
                "type": "table",
                "destination": "level2-a",
                "tableMapping": {
                  "count": "count",
                  "itemId": "itemId"
                }
              },
              "b": {
                "type": "table",
                "destination": "level2-b",
                "tableMapping": {
                  "count": "count",
                  "itemId": "itemId"
                }
              }
            }
          }
        }
      }
    ]
  }
}
JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($json, JsonEncoder::FORMAT);
        $application = new Application($config);
        $application->actionRun($this->path);

        // Root level
        $rootPath = $this->path . '/root.csv';
        $this->assertFileExists($rootPath);
        $rootItems = file_get_contents($rootPath);
        $expectedRootItems = <<<END
"id","level1"
"5716054bee6e764c94fa7aaa","abd3a376c3391349acdd8314c86d52c2"
"5716054bee6e764c94fa7aab","242e5b0bd72bbe997aa19b77707bae5a"
"5716054bee6e764c94fa7aac","da9abe6c21ccf7b9eb54d279b0b70326"
"5716054bee6e764c94fa7aad","17622ddee10ab75bd3de2eb4d96532c1"

END;
        $this->assertSame($expectedRootItems, $rootItems);

        // Level 1 - SAME content -> DIFFERENT keys!!!
        $level1Path = $this->path . '/level1.csv';
        $this->assertFileExists($level1Path);
        $level1Items = file_get_contents($level1Path);
        $expectedLevel1Items = <<<END
"a","b","level2-a","level2-b","root_pk"
"123","123","ad1c2165423aa6c30ae7b2515625a1ab","ad1c2165423aa6c30ae7b2515625a1ab","abd3a376c3391349acdd8314c86d52c2"
"123","123","cf90539a89e1696793d8f8c29b89ca61","cf90539a89e1696793d8f8c29b89ca61","242e5b0bd72bbe997aa19b77707bae5a"
"456","123","77d54caf02384eea2f166dff9cdb70c1","77d54caf02384eea2f166dff9cdb70c1","da9abe6c21ccf7b9eb54d279b0b70326"
"456","123","854dab3d9cfa9d5994da2ddeec1e9446","854dab3d9cfa9d5994da2ddeec1e9446","17622ddee10ab75bd3de2eb4d96532c1"

END;
        $this->assertSame($expectedLevel1Items, $level1Items);

        // Level 2-A
        $level2APath = $this->path . '/level2-a.csv';
        $this->assertFileExists($level2APath);
        $level2AItems = file_get_contents($level2APath);
        $expectedLevel2AItems = <<<END
"count","itemId","level1_pk"
"1","123","ad1c2165423aa6c30ae7b2515625a1ab"
"1","123","cf90539a89e1696793d8f8c29b89ca61"
"20","456","77d54caf02384eea2f166dff9cdb70c1"
"20","456","854dab3d9cfa9d5994da2ddeec1e9446"

END;
        $this->assertSame($expectedLevel2AItems, $level2AItems);

        // Level 2-B
        $level2BPath = $this->path . '/level2-b.csv';
        $this->assertFileExists($level2APath);
        $level2BItems = file_get_contents($level2BPath);
        $expectedLevel2BItems = <<<END
"count","itemId","level1_pk"
"1","123","ad1c2165423aa6c30ae7b2515625a1ab"
"1","123","cf90539a89e1696793d8f8c29b89ca61"
"1","123","77d54caf02384eea2f166dff9cdb70c1"
"1","123","854dab3d9cfa9d5994da2ddeec1e9446"

END;
        $this->assertSame($expectedLevel2BItems, $level2BItems);
    }
}
