<?php

namespace Sacapsystems\LaravelAzureMaps\Builders;

use Illuminate\Support\Facades\Http;
use Sacapsystems\LaravelAzureMaps\Exceptions\AzureMapsException;

class QueryBuilder
{
    private array $params;
    private string $baseUrl;
    private string $apiKey;

    private const DEFAULT_PARAMS = [
        'api-version' => '1.0',
        'limit' => 5
    ];

    public function __construct(string $baseUrl, string $apiKey)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    public function newSearch(string $query, ?string $categorySet = null): self
    {
        $this->params = self::DEFAULT_PARAMS + [
            'subscription-key' => $this->apiKey,
            'query' => $query
        ];

        if ($categorySet) {
            $this->params['categorySet'] = $categorySet;
        }

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->params['limit'] = $limit;
        return $this;
    }

    /**
     * @param string|array $countryCode
     */
    public function country($countryCode): self
    {
        $this->params['countrySet'] = is_array($countryCode)
            ? implode(',', $countryCode)
            : $countryCode;
        return $this;
    }

    public function location(float $lat, float $lon, int $radius = 50000): self
    {
        $this->params['lat'] = $lat;
        $this->params['lon'] = $lon;
        $this->params['radius'] = $radius;
        return $this;
    }

    public function get(): string
    {
        $response = Http::get($this->baseUrl, $this->params);

        if ($response->failed()) {
            throw new AzureMapsException('Failed to fetch search results');
        }

        return $this->formatResults($response->json());
    }

    private function formatResults(array $results): string
    {
        $formattedResults = collect($results['results'] ?? [])->map(function ($result) {
            $address = $result['address'] ?? [];

            $subdivision = isset($address['municipalitySubdivision']) ? $address['municipalitySubdivision'] . ', ' : '';
            $municipality = $address['municipality'] ?? '';
            $line2 = trim($subdivision . $municipality);

            return [
                'name' => $result['poi']['name'] ?? '',
                'address' => [
                    'line1' => trim(($address['streetNumber'] ?? '') . ' ' . ($address['streetName'] ?? '')),
                    'line2' => $line2,
                    'suburb' => $address['municipalitySubdivision'] ?? null,
                    'city' => $address['municipality'] ?? null,
                    'postalCode' => $address['postalCode'] ?? null,
                    'province' => $address['countrySubdivision'] ?? null,
                    'provinceCode' => $address['countrySubdivisionCode'] ?? null,
                    'country' => $address['country'] ?? null,
                    'countryCodeISO3' => $address['countryCodeISO3'] ?? null,
                ],
                'coordinates' => [
                    'lat' => $result['position']['lat'] ?? null,
                    'lng' => $result['position']['lon'] ?? null,
                ],
            ];
        })->toArray();

        return json_encode($formattedResults);
    }
}
