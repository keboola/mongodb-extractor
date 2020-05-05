<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

trait CreateExtractorTrait
{
    protected UriFactory $uriFactory;

    protected ExportCommandFactory $exportCommandFactory;

    public function createExtractor(array $parameters): Extractor
    {
        return new Extractor($this->uriFactory, $this->exportCommandFactory, $parameters);
    }
}
