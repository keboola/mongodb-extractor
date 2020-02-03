<?php

namespace Keboola\MongoDbExtractor\Unit;

use PHPUnit\Framework\TestCase;
use Keboola\MongoDbExtractor\ExportCommandFactory;
use Keboola\MongoDbExtractor\UserException;

class ExportCommandFactoryTest extends TestCase
{
    /** @var ExportCommandFactory */
    private $commandFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->commandFactory = new ExportCommandFactory();
    }

    public function testCreateMinimal()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'database' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testMongoDbProtocol()
    {
        $options = [
            'protocol' => 'mongodb',
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --uri 'mongodb://localhost:27017/myDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testMongoDbSrvProtocol()
    {
        $options = [
            'protocol' => 'mongodb+srv',
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
        ];

        $command = $this->commandFactory->create($options);

        // URI starting with mongodb+srv:// must not include a port number
        $expectedCommand = <<<BASH
mongoexport --uri 'mongodb+srv://localhost/myDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testWithCustomAuthenticationDatabase()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'database' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
            // auth with custom auth database
            'username' => 'user',
            'password' => 'pass',
            'authenticationDatabase' => 'myAuthDatabase',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --username 'user' --password 'pass' --authenticationDatabase 'myAuthDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testWithEmptyCustomAuthenticationDatabase()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'database' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
            // auth with empty custom auth database
            'username' => 'user',
            'password' => 'pass',
            'authenticationDatabase' => ' ',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --username 'user' --password 'pass' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testCreateFull()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'username' => 'user',
            'password' => 'pass',
            'database' => 'myDatabase',
            'collection' => 'myCollection',
            'query' => '{a: "b"}',
            'sort' => '{a: 1, b: -1}',
            'limit' => 10,
            'out' => '/tmp/create-test.json',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --username 'user' --password 'pass' --collection 'myCollection' --query '{a: "b"}' --sort '{a: 1, b: -1}' --limit '10' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testWithEmptyOptionalValues()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'username' => 'user',
            'password' => 'pass',
            'database' => 'myDatabase',
            'collection' => 'myCollection',
            'query' => '',
            'sort' => ' ',
            'limit' => '  ',
            'out' => '/tmp/create-test.json',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --username 'user' --password 'pass' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }
}
