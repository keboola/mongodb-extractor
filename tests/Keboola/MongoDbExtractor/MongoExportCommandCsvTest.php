<?php

namespace Keboola\MongoDbExtractor;

class MongoExportCommandCsvTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateMinimal()
    {
        $options = [
            'host' => 'localhost',
            'port' => 27017,
            'db' => 'myDatabase',
            'collection' => 'myCollection',
            'fields' => [
                'field',
            ],
            'out' => '/tmp/create-test.csv',
        ];

        $command = new MongoExportCommandCsv($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --db 'myDatabase' --collection 'myCollection' --fields 'field' --type 'csv' --out '/tmp/create-test.csv'
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
            'fields' => [
                'field1',
                'field2',
            ],
            'sort' => '{a: 1, b: -1}',
            'limit' => 10,
            'out' => '/tmp/create-test.csv',
        ];

        $command = new MongoExportCommandCsv($options);
        $expectedCommand = <<<BASH
mongoexport --host 'localhost' --port '27017' --username 'user' --password 'pass' --db 'myDatabase' --collection 'myCollection' --fields 'field1,field2' --query '{a: "b"}' --sort '{a: 1, b: -1}' --limit '10' --type 'csv' --out '/tmp/create-test.csv'
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
            'fields' => [
                'field',
            ],
            'out' => '/tmp/validate-test.csv',
        ];

        new MongoExportCommandCsv($options);
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
            'fields' => [
                'field',
            ],
            'out' => '/tmp/validate-test.csv',
        ];

        new MongoExportCommandCsv($options);
    }

    public function testCreateWithMissingRequiredParam()
    {
        $this->expectException(MongoExportCommandCsvException::class);

        new MongoExportCommandCsv([]);
    }
}
