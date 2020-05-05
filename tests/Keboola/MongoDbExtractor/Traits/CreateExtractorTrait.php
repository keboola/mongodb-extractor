<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

trait CreateExtractorTrait
{
    /** @var UriFactory */
    protected $uriFactory;

    /** @var ExportCommandFactory */
    protected $exportCommandFactory;

    public function createExtractor(array $parameters): Extractor
    {
        return new Extractor($this->uriFactory, $this->exportCommandFactory, $parameters);
    }
}
