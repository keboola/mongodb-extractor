<?php

namespace Keboola\MongoDbExtractor;

class MongoExportCommandJsonTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateMinimal()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/create-test.json',
        ];

        $command = new MongoExportCommandJson($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --collection 'myCollection' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command->getCommand());
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

        $command = new MongoExportCommandJson($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --username 'user' --password 'pass' --db 'myDatabase' --collection 'myCollection' --query '{a: "b"}' --sort '{a: 1, b: -1}' --limit '10' --type 'json' --out '/tmp/create-test.json'
BASH;

        $this->assertSame($expectedCommand, $command->getCommand());
    }

    public function testValidateWithoutUser()
    {
        $this->expectException(MongoExportCommandCsvException::class);

        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'password' => 'password',
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/validate-test.json',
        ];

        new MongoExportCommandJson($options);
    }

    public function testValidateWithoutPassword()
    {
        $this->expectException(MongoExportCommandCsvException::class);

        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'username' => 'user',
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'out' => '/tmp/validate-test.json',
        ];

        new MongoExportCommandJson($options);
    }

    public function testCreateWithMissingRequiredParam()
    {
        $this->expectException(MongoExportCommandCsvException::class);

        new MongoExportCommandJson([]);
    }
}