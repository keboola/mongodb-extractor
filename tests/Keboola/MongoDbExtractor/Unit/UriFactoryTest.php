<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Unit;

use PHPUnit\Framework\TestCase;
use Keboola\MongoDbExtractor\UriFactory;

class UriFactoryTest extends TestCase
{
    /** @var UriFactory */
    private $uriFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uriFactory = new UriFactory();
    }

    public function testCreateMinimal(): void
    {
        $this->assertSame('mongodb://localhost:27017/myDatabase', $this->uriFactory->create([
            'host' => 'localhost',
            'port' => 27017,
            'database' => 'myDatabase',
        ]));
    }

    public function testCreateUserAndPassword(): void
    {
        $this->assertSame('mongodb://user:pass@localhost:27017/myDatabase', $this->uriFactory->create([
            'host' => 'localhost',
            'port' => 27017,
            'database' => 'myDatabase',
            'user' => 'user',
            'password' => 'pass',
        ]));
    }

    public function testCreateAuthDb(): void
    {
        $this->assertSame(
            'mongodb://user:pass@localhost:27017/myDatabase?authSource=authDb',
            $this->uriFactory->create([
                'host' => 'localhost',
                'port' => 27017,
                'database' => 'myDatabase',
                'user' => 'user',
                'password' => 'pass',
                'authenticationDatabase' => 'authDb',
            ])
        );
    }
}
