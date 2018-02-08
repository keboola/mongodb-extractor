<?php

namespace Keboola\MongoDbExtractor\Parser;

use Keboola\Csv\CsvFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class Raw
{
    /** @var CsvFile */
    private $outputFile;

    /** @var Filesystem */
    private $filesystem;

    /** @var JsonEncode */
    private $jsonEncode;

    /** @var array */
    private $manifestOptions;

    /** @var bool */
    private $setIdAsPrimaryKey = true;

    public function __construct(string $name, string $outputPath, array $manifestOptions)
    {
        // create csv file and its header
        $this->outputFile = new CsvFile($outputPath . '/' . $name . '.csv');
        $this->outputFile->writeRow(['id', 'data']);

        $this->manifestOptions = $manifestOptions;

        $this->filesystem = new Filesystem;
        $this->jsonEncode = new JsonEncode;
    }

    /**
     * Parses provided data and writes to output files
     * @param array $data
     */
    public function parse(array $data)
    {
        $item = reset($data);

        if (!empty($data)) {
            $this->writerRowToOutputFile($item);
        }
    }

    public function writeManifestFile()
    {
        $manifest = [
            'primary_key' => $this->setIdAsPrimaryKey ? ['id']: [],
            'incremental' => $this->manifestOptions['incremental'],
        ];

        $outputCsv = $this->outputFile->getPathname();

        $this->filesystem->dumpFile(
            $outputCsv . '.manifest',
            $this->jsonEncode->encode($manifest, JsonEncoder::FORMAT)
        );
    }

    private function writerRowToOutputFile($item)
    {
        if (property_exists($item, '_id')) {
            $type = gettype($item->{'_id'});
            if ($type === 'object' && property_exists($item->{'_id'}, '$oid')) {
                $this->outputFile->writeRow([
                    $item->{'_id'}->{'$oid'},
                    \json_encode($item)
                ]);
            } else if (in_array($type, ['double', 'string', 'integer'])) {
                $this->outputFile->writeRow([
                    $item->{'_id'},
                    \json_encode($item)
                ]);
            } else {
                $this->outputFile->writeRow([
                    '',
                    \json_encode($item)
                ]);
                $this->setIdAsPrimaryKey = false;
            }
        } else {
            $this->outputFile->writeRow([
                '',
                \json_encode($item)
            ]);
            $this->setIdAsPrimaryKey = false;
        }
    }
}
