<?php

namespace Keboola\MongoDbExtractor;

class MongoExportCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateMinimal()
    {
        $connectionParams = [
            'host' => 'localhost',
            'port' => 27017,
        ];
        $exportParams = [
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'fields' => [
                'field',
            ],
            'name' => 'create-test',
        ];

        $command = new MongoExportCommand($connectionParams, $exportParams, '/tmp');
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --collection 'myCollection' --fields 'field' --type 'csv' --out '/tmp/create-test.csv'
BASH;

        $this->assertSame($expectedCommand, $command->getCommand());
    }

    public function testCreateFull()
    {
        $connectionParams = [
            'host' => 'localhost',
            'port' => 27017,
            'user' => 'user',
            'password' => 'pass',
        ];
        $exportParams = [
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'query' => '{a: "b"}',
            'fields' => [
                'field1',
                'field2',
            ],
            'sort' => '{a: 1, b: -1}',
            'limit' => 10,
            'name' => 'create-test',
        ];

        $command = new MongoExportCommand($connectionParams, $exportParams, '/tmp');
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --username 'user' --password 'pass' --db 'myDatabase' --collection 'myCollection' --fields 'field1,field2' --query '{a: "b"}' --sort '{a: 1, b: -1}' --limit '10' --type 'csv' --out '/tmp/create-test.csv'
BASH;

        $this->assertSame($expectedCommand, $command->getCommand());
    }

    public function testValidateWithoutUser()
    {
        $this->expectException(MongoExportCommandException::class);

        $connectionParams = [
            'host' => 'localhost',
            'port' => 27017,
            'password' => 'password'
        ];
        $exportParams = [
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'fields' => [
                'field',
            ],
            'name' => 'validate-test',
        ];

        new MongoExportCommand($connectionParams, $exportParams, '/tmp');
    }

    public function testValidateWithoutPassword()
    {
        $this->expectException(MongoExportCommandException::class);

        $connectionParams = [
            'host' => 'localhost',
            'port' => 27017,
            'user' => 'user'
        ];
        $exportParams = [
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'fields' => [
                'field',
            ],
            'name' => 'validate-test',
        ];

        new MongoExportCommand($connectionParams, $exportParams, '/tmp');
    }

    public function testCreateWithMissingRequiredParam()
    {
        $this->expectException(MongoExportCommandException::class);

        new MongoExportCommand([], [], '/tmp');
    }
}
