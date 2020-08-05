<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests;

use Keboola\MongoDbExtractor\ExportCommandFactory;
use Keboola\MongoDbExtractor\UriFactory;
use League\Uri\Components\Query;
use Mockery;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Test connection using mongodb+srv:// URI prefix
 * See: https://docs.mongodb.com/manual/reference/connection-string/#dns-seedlist-connection-format
 */
class ExtractorClusterConnectionTest extends ExtractorTestCase
{
    private Filesystem $fs;

    protected string $path = '/tmp/extractor-cluster';

    protected function setUp(): void
    {
        parent::setUp();

        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        // Protocol mongodb+srv:// automatically sets the ssl=true
        // ... but setup MongoDB cluster with TLS/SSL in docker-compose is hard
        // ... therefore is SSL disabled in tests by connection string
        // See docker-compose.yml
        $originUriFactory = $this->uriFactory;
        $this->uriFactory = Mockery::mock(UriFactory::class);
        $this
            ->uriFactory
            ->shouldReceive('create')
            ->andReturnUsing(function (array $params) use ($originUriFactory) {
                $uri = $originUriFactory->create($params);
                $query = $uri->getQuery();
                $uri->setQuery($query->withPair('ssl', 'false'));
                return $uri;
            });
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->path);
    }

    protected function getConfig(): array
    {
        $config = <<<JSON
{
  "parameters": {
    "db": {
      "protocol": "mongodb+srv",
      "host": "mongodb.cluster.local",
      "port": 27017,
      "database": "test"
    }
  }
}
JSON;
        return (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($config, JsonEncoder::FORMAT);
    }
}
