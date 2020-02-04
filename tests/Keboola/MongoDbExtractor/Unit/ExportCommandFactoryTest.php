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

    public function testWithCustomAuthenticationDatabase()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
            // auth with custom auth database
            'username' => 'user',
            'password' => 'pass',
            'authenticationDatabase' => 'myAuthDatabase',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --uri 'mongodb://user:pass@localhost:27017/myDatabase?authSource=myAuthDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testWithEmptyCustomAuthenticationDatabase()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
            // auth with empty custom auth database
            'username' => 'user',
            'password' => 'pass',
            'authenticationDatabase' => ' ',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --uri 'mongodb://user:pass@localhost:27017/myDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
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
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'query' => '{a: "b"}',
            'sort' => '{a: 1, b: -1}',
            'limit' => 10,
            'out' => '/tmp/create-test.json',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --uri 'mongodb://user:pass@localhost:27017/myDatabase' --collection 'myCollection' --query '{a: "b"}' --sort '{a: 1, b: -1}' --limit '10' --type 'json' --out '/tmp/create-test.json'
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
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'query' => '',
            'sort' => ' ',
            'limit' => '  ',
            'out' => '/tmp/create-test.json',
        ];

        $command = $this->commandFactory->create($options);
        $expectedCommand = <<<BASH
mongoexport --uri 'mongodb://user:pass@localhost:27017/myDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command);
    }

    public function testValidateWithoutUser()
    {
        $this->expectException(UserException::class);

        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'password' => 'password',
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/validate-test.json',
        ];

        $this->commandFactory->create($options);
    }

    public function testValidateWithoutPassword()
    {
        $this->expectException(UserException::class);

        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'username' => 'user',
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/validate-test.json',
        ];

        $this->commandFactory->create($options);
    }

    public function testCreateWithMissingRequiredParam()
    {
        $this->expectException(UserException::class);
        $options = [];
        $this->commandFactory->create($options);
    }
}
