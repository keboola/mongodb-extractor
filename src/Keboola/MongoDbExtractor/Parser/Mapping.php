<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Parser;

use Keboola\CsvMap\Mapper;
use Nette\Utils\Strings;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class Mapping
{
    /** @var array */
    private $mapping;

    /** @var string */
    private $path;

    /** @var Filesystem */
    private $filesystem;

    /** @var JsonEncode */
    private $jsonEncode;

    /** @var string  */
    private $name;

    /** @var array */
    private $manifestOptions;

    public function __construct(string $name, array $mapping, string $outputPath, array $manifestOptions)
    {
        $this->name = $name;
        $this->mapping = $mapping;
        $this->path = $outputPath;
        $this->manifestOptions = $manifestOptions;

        $this->filesystem = new Filesystem;
        $this->jsonEncode = new JsonEncode;
    }

    /**
     * Parses provided data and writes to output files
     * @param array $data
     * @throws \Exception
     */
    public function parse(array $data): void
    {
        $mapper = new Mapper($this->mapping, $this->name);
        $mapper->parse($data);

        foreach ($mapper->getCsvFiles() as $file) {
            if ($file !== null) {
                $name = Strings::webalize($file->getName());
                $outputCsv = $this->path . '/' . $name . '.csv';

                $content = file_get_contents($file->getPathname());

                // csv-map doesn't have option to skip header yet,
                // so we skip header if file exists
                if ($this->filesystem->exists($outputCsv)) {
                    $contentArr = explode("\n", $content);
                    array_shift($contentArr);
                    $content = implode("\n", $contentArr);
                }

                if (@file_put_contents($outputCsv, $content, FILE_APPEND | LOCK_EX) === false) {
                    throw new \Exception('Failed write to file "' . $outputCsv);
                }

                $manifest = [
                    'primary_key' => $file->getPrimaryKey(true),
                    'incremental' => isset($this->manifestOptions['incremental'])
                        ? (bool) $this->manifestOptions['incremental']
                        : false,
                ];

                if (!$this->filesystem->exists($outputCsv . '.manifest')) {
                    $this->filesystem->dumpFile(
                        $outputCsv . '.manifest',
                        $this->jsonEncode->encode($manifest, JsonEncoder::FORMAT)
                    );
                }

                $this->filesystem->remove($file->getPathname());
            }
        }
    }
}
