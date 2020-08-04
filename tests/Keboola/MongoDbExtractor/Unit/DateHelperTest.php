<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests\Unit;

use Keboola\MongoDbExtractor\DateHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    /**
     * @dataProvider getConvertDatesDataProvider
     */
    public function testConvertDatesToString(string $input, bool $isoDatePrefix, string $expectedOutput): void
    {
        Assert::assertSame($expectedOutput, DateHelper::convertDatesToString($input, $isoDatePrefix));
    }

    /**
     * @dataProvider getFixIsoDateDataProvider
     */
    public function testFixIsoDateInGteQuery(string $input, string $expectedOutput): void
    {
        Assert::assertSame($expectedOutput, DateHelper::fixIsoDateInGteQuery($input));
    }

    public function getConvertDatesDataProvider(): array
    {
        // Note: Unreal cases are also tested to make it clear that REGEXP is working properly.
        return [
            'no-prefix' => [
                '{ "id" : 1, "date": {"$date": "2020-05-18T16:00:00Z"}, "string":  "testString"}',
                false,
                '{ "id" : 1, "date": "2020-05-18T16:00:00Z", "string":  "testString"}',
            ],
            'prefix' =>[
                '{ "id" : 1, "date": {"$date": "2020-05-18T16:00:00Z"}, "string":  "testString"}',
                true,
                '{ "id" : 1, "date": "ISODate(\"2020-05-18T16:00:00Z\")", "string":  "testString"}',
            ],
            'escaping' =>[
                '{ "id" : 1, "date": {"$date": "2020-05-\"\"\"18T16:00:00Z"}, "string":  "testString"}',
                false,
                '{ "id" : 1, "date": "2020-05-\"\"\"18T16:00:00Z", "string":  "testString"}',
            ],
            'escaping-prefix' =>[
                '{ "id" : 1, "date": {"$date": "2020-05-\"\"\"18T16:00:00Z"}, "string":  "testString"}',
                true,
                '{ "id" : 1, "date": "ISODate(\"2020-05-\\\\\"\\\\\"\\\\\"18T16:00:00Z\")", "string":  "testString"}',
            ],
            'no-spaces' => [
                '{"id":1,"date":{"$date": "2020-05-18T16:00:00Z"},"string":"testString"}',
                false,
                '{"id":1,"date":"2020-05-18T16:00:00Z","string":"testString"}',
            ],
            'no-spaces-prefix' =>[
                '{"id":1,"date":{"$date":"2020-05-18T16:00:00Z"},"string":"testString"}',
                true,
                '{"id":1,"date":"ISODate(\"2020-05-18T16:00:00Z\")","string":"testString"}',
            ],
            'empty' => [
                '{ "id" : 1, "date": {"$date": ""}, "string":  "testString"}',
                false,
                '{ "id" : 1, "date": "", "string":  "testString"}',
            ],
            'empty-prefix' =>[
                '{ "id" : 1, "date": {"$date": ""}, "string":  "testString"}',
                true,
                '{ "id" : 1, "date": "ISODate(\"\")", "string":  "testString"}',
            ],
            'invalid-1' => [
                '{ "id" : 1, "date": {"$date": "2020-05-18T16:00:00Z}, "string":  "testString"}',
                false,
                '{ "id" : 1, "date": {"$date": "2020-05-18T16:00:00Z}, "string":  "testString"}',
            ],
            'invalid-1-prefix' =>[
                '{ "id" : 1, "date": {"$date": "2020-05-18T16:00:00Z}, "string":  "testString"}',
                true,
                '{ "id" : 1, "date": {"$date": "2020-05-18T16:00:00Z}, "string":  "testString"}',
            ],
            'invalid-2' => [
                '{ "id" : 1, "date": {"$date": 1234, "string":  "testString"}',
                false,
                '{ "id" : 1, "date": {"$date": 1234, "string":  "testString"}',
            ],
            'invalid-2-prefix' =>[
                '{ "id" : 1, "date": {"$date": 1234, "string":  "testString"}',
                true,
                '{ "id" : 1, "date": {"$date": 1234, "string":  "testString"}',
            ],
        ];
    }

    public function getFixIsoDateDataProvider(): array
    {
        // Note: Unreal cases are also tested to make it clear that REGEXP is working properly.
        return [
            'simple' => [
                '{"$gte":"ISODate(\"2020-05-18T16:00:00Z\")"}',
                '{"$gte":ISODate("2020-05-18T16:00:00Z")}',
            ],
            'empty-1' => [
                '{"$gte":"ISODate(\"\")"}',
                '{"$gte":ISODate("")}',
            ],
            'escaping' => [
                '{"$gte":"ISODate(\"2020-05-\\\\\"18\\\\\"T16:00:00Z\")"}',
                '{"$gte":ISODate("2020-05-\"18\"T16:00:00Z")}',
            ],
            'invalid-1' => [
                '{"$gte":"ISODate(\"2020-05\"\"-18T16:00:00Z\")"}',
                '{"$gte":ISODate("2020-05""-18T16:00:00Z")}',
            ],
            'invalid-2' => [
                '{"$gte":"ISODate()"}',
                '{"$gte":"ISODate()"}',
            ],
            'invalid-3' => [
                '{"$gte":"abc"}',
                '{"$gte":"abc"}',
            ],
            'invalid-4' => [
                '{"$gte":1234}',
                '{"$gte":1234}',
            ],
        ];
    }
}
