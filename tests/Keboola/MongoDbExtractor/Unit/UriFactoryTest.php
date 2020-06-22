<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Keboola\MongoDbExtractor\UriFactory;

class UriFactoryTest extends TestCase
{
    private UriFactory $uriFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uriFactory = new UriFactory();
    }

    /**
     * @dataProvider paramsDataProvider
     */
    public function testUriFactory(string $expected, array $params): void
    {
        $this->assertSame($expected, $this->uriFactory->create($params));
    }

    public function paramsDataProvider(): array
    {
        return [
            'minimal' => [
                'mongodb://localhost:27017/myDatabase',
                [
                    'host' => 'localhost',
                    'port' => 27017,
                    'database' => 'myDatabase',

                ],
            ],
            'userAndPassword' => [
                'mongodb://user:pass@localhost:27017/myDatabase',
                [
                    'host' => 'localhost',
                    'port' => 27017,
                    'database' => 'myDatabase',
                    'user' => 'user',
                    'password' => 'pass',
                ],
            ],
            'authDb' => [
                'mongodb://user:pass@localhost:27017/myDatabase?authSource=authDb',
                [
                    'host' => 'localhost',
                    'port' => 27017,
                    'database' => 'myDatabase',
                    'user' => 'user',
                    'password' => 'pass',
                    'authenticationDatabase' => 'authDb',
                ],
            ],
            'authDb-escape' => [
                'mongodb://user:pass@localhost:27017/myDatabase?authSource=a%2Fb%2Fc%24%25%5E',
                [
                    'host' => 'localhost',
                    'port' => 27017,
                    'database' => 'myDatabase',
                    'user' => 'user',
                    'password' => 'pass',
                    'authenticationDatabase' => 'a/b/c$%^',
                ],
            ],
            'database-escape' => [
                'mongodb://user:pass@localhost:27017/a%2Fb%2Fc%24%25%5E?authSource=authDb',
                [
                    'host' => 'localhost',
                    'port' => 27017,
                    'database' => 'a/b/c$%^',
                    'user' => 'user',
                    'password' => 'pass',
                    'authenticationDatabase' => 'authDb',
                ],
            ],
        ];
    }
}
