<?php

namespace Sacapsystems\LaravelAzureMaps\Services;

use Illuminate\Support\Facades\Config;
use Sacapsystems\LaravelAzureMaps\Builders\QueryBuilder;

class AzureMapsService
{
    private QueryBuilder $queryBuilder;

    public function __construct()
    {
        $this->queryBuilder = new QueryBuilder(
            Config::get('azure-maps.base_url'),
            Config::get('azure-maps.api_key')
        );
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
