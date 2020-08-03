<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

class DateHelper
{
    /**
     * Date fields in MongoDB export output, eg. {"$date":"2016-05-18T16:00:00Z"}
     * are converted to string with surrounding slashes (so JSON is still valid).
     * ISODate prefix is optional.
     */
    public static function convertDatesToString(string $input, bool $isoDate = false): string
    {
        return preg_replace_callback(
            '~{"\$date":(?>\s)*("(?>(?>\\\")|[^"])*")}~',
            function (array $m) use ($isoDate): string {
                return $isoDate ? '"ISODate(' . addslashes($m[1]) .')"' : $m[1];
            },
            $input
        );
    }

    /**
     * Incremental fetching query is build by json_encode funciton,
     * but MongoDB is using extended JSON with ISODate without quotes.
     * So it is needed to remove surrounding slashes from "ISODate(...)".
     */
    public static function fixIsoDateInGteQuery(string $input): string
    {
        return preg_replace_callback(
            '~"\$gte":"ISODate\((\\\"(?>(?>\\\")|[^"])*\\\")\)"~',
            function (array $m): string {
                return '"$gte":ISODate(' . stripslashes($m[1]) . ')';
            },
            $input
        );
    }
}
