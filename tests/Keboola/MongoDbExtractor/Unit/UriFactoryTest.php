<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests\Unit;

use Keboola\MongoDbExtractor\UserException;
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
        $this->assertSame($expected, (string) $this->uriFactory->create($params));
    }

    /**
     * @dataProvider invalidCustomUriProvider
     */
    public function testInvalidCustomUri(array $params, string $expectedMsg): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage($expectedMsg);
        $this->uriFactory->create($params);
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
                'mongodb://user:pass@localhost:27017/myDatabase?authSource=a/b/c$%25%5E',
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
            'vpn-host' => [
                'mongodb://user:pass@aBC1cmVrABc.vpn2keboola.com:27017/a%2Fb%2Fc%24%25%5E?authSource=authDb',
                [
                    'host' => 'aBC1cmVrABc.vpn2keboola.com',
                    'port' => 27017,
                    'database' => 'a/b/c$%^',
                    'user' => 'user',
                    'password' => 'pass',
                    'authenticationDatabase' => 'authDb',
                ],
            ],
            'mongodb+srv' => [
                'mongodb+srv://u%24er:pa%24%24@mongodb.cluster.local/myDatabase?authSource=authDb',
                [
                    'protocol' => 'mongodb+srv',
                    'host' => 'mongodb.cluster.local',
                    'database' => 'myDatabase',
                    'user' => 'u$er',
                    'password' => 'pa$$',
                    'authenticationDatabase' => 'authDb',
                ],
            ],
            'custom-uri' => [
                'mongodb://user%40:pas%24%24word%40@localhost:27017/db?authSource=authDb&replicaSet=myRepl',
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://user%40@localhost:27017/db?authSource=authDb&replicaSet=myRepl',
                    'password' => 'pas$$word@',
                ],
            ],
            'vpn-custom-uri' => [
                'mongodb://user%40:pas%24%24word%40@aBC1cmVrABc.vpn2keboola.com/db?authSource=authDb&replicaSet=myRepl',
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://user%40@aBC1cmVrABc.vpn2keboola.com/db?authSource=authDb&replicaSet=myRepl',
                    'password' => 'pas$$word@',
                ],
            ],
            // Examples from MongoDB documentation
            // https://docs.mongodb.com/manual/reference/connection-string/#connections-connection-examples
            'doc-1' => [
                'mongodb://sysop:pa%24%24@localhost/records',
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://sysop@localhost/records',
                    'password' => 'pa$$',
                ],
            ],
            'doc-2-replica-set' => [
                'mongodb://user:pa%24%24@db1.example.net,db2.example.com/db?replicaSet=test',
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://user@db1.example.net,db2.example.com/db?replicaSet=test',
                    'password' => 'pa$$',
                ],
            ],
            'doc-3-replica-set' => [
                'mongodb://user:pa%24%24@localhost,localhost:27018,localhost:27019/db?replicaSet=test',
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://user@localhost,localhost:27018,localhost:27019/db?replicaSet=test',
                    'password' => 'pa$$',
                ],
            ],
        ];
    }

    public function invalidCustomUriProvider(): array
    {
        return [
            'empty' => [
                [
                    'protocol' => 'custom_uri',
                    'uri' => '',
                    'password' => 'pas$$word',
                ],
                'Connection URI must start with "mongodb://" or "mongodb+srv://".',
            ],
            'invalid-protocol' => [
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'http://test.com',
                    'password' => 'pas$$word',
                ],
                'Connection URI must start with "mongodb://" or "mongodb+srv://".',
            ],
            'missing-user' => [
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://localhost:27017/?authSource=authDb',
                    'password' => 'pas$$word',
                ],
                'Connection URI must contain user, eg: "mongodb://user@hostname/database',
            ],
            'password-in-uri' => [
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://user:password@localhost:27017/?authSource=authDb',
                    'password' => 'pas$$word',
                ],
                'Connection URI must not contain the password. The password is a separate item for security reasons.',
            ],
            // Examples from MongoDB documentation - valid but not usable in extractor
            // https://docs.mongodb.com/manual/reference/connection-string/#connections-connection-examples
            'doc-1' => [
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://localhost',
                    'password' => 'pa$$',
                ],
                'Connection URI must contain user, eg: "mongodb://user@hostname/database".',
            ],
            'doc-2' => [
                [
                    'protocol' => 'custom_uri',
                    'uri' => 'mongodb://sysop@localhost',
                    'password' => 'pass',
                ],
                'Connection URI must contain the database, eg: "mongodb://user@hostname/database".',
            ],
        ];
    }
}
