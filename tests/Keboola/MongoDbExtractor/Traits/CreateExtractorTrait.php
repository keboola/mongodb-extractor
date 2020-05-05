<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor\Tests\Traits;

use Keboola\MongoDbExtractor\ExportCommandFactory;
use Keboola\MongoDbExtractor\Extractor;
use Keboola\MongoDbExtractor\UriFactory;

trait CreateExtractorTrait
{
    protected UriFactory $uriFactory;

    protected ExportCommandFactory $exportCommandFactory;

    public function createExtractor(array $parameters): Extractor
    {
        return new Extractor($this->uriFactory, $this->exportCommandFactory, $parameters);
    }
}
