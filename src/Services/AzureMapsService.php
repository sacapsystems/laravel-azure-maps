<?php

namespace Sacapsystems\LaravelAzureMaps\Services;

use Illuminate\Support\Facades\Config;
use Sacapsystems\LaravelAzureMaps\Builders\QueryBuilder;

class AzureMapsService
{
    private QueryBuilder $queryBuilder;
    private $queryBuilderFactory;

    public function __construct(?callable $queryBuilderFactory = null)
    {
        $this->queryBuilderFactory = $queryBuilderFactory ?? function () {
            return new QueryBuilder(
                Config::get('azure-maps.base_url'),
                Config::get('azure-maps.api_key')
            );
        };

        $this->queryBuilder = ($this->queryBuilderFactory)();
    }

    public function searchAddress(string $query): QueryBuilder
    {
        return $this->queryBuilder->newSearch($query);
    }

    public function searchSchools(string $query): QueryBuilder
    {
        return $this->queryBuilder->newSearch($query, '7372');
    }
}
