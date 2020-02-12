<?php

namespace Keboola\MongoDbExtractor\Unit;

use PHPUnit\Framework\TestCase;
use Keboola\MongoDbExtractor\UriFactory;

class UriFactoryTest extends TestCase
{
    /** @var UriFactory */
    private $uriFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->uriFactory = new UriFactory();
    }

    public function testCreateMinimal()
    {
        $this->assertSame('mongodb://localhost:27017/myDatabase', $this->uriFactory->create([
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
        ]));
    }

    public function testCreateUserAndPassword()
    {
        $this->assertSame('mongodb://user:pass@localhost:27017/myDatabase', $this->uriFactory->create([
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'username' => 'user',
            'password' => 'pass',
        ]));
    }

    public function testCreateAuthDb()
    {
        $this->assertSame('mongodb://user:pass@localhost:27017/myDatabase?authSource=authDb', $this->uriFactory->create([
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'username' => 'user',
            'password' => 'pass',
            'authenticationDatabase' => 'authDb',
        ]));
    }
}
