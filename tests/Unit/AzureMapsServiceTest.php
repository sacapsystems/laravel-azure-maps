<?php

namespace Sacapsystems\LaravelAzureMaps\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Sacapsystems\LaravelAzureMaps\Exceptions\AzureMapsException;
use Sacapsystems\LaravelAzureMaps\Services\AzureMapsService;
use Sacapsystems\LaravelAzureMaps\Tests\TestCase;

class AzureMapsServiceTest extends TestCase
{
    protected $service;
    protected $mockResponse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AzureMapsService();
        $this->mockResponse = [
           'summary' => ['numResults' => 1],
           'results' => [
               [
                   'address' => [
                       'streetNumber' => '123',
                       'streetName' => 'Main Street',
                       'municipality' => 'Cape Town',
                       'countrySubdivision' => 'Western Cape',
                       'countrySubdivisionCode' => 'WC',
                       'country' => 'South Africa',
                       'countryCodeISO3' => 'ZAF',
                       'postalCode' => '8001',
                       'municipalitySubdivision' => 'City Bowl',
                   ],
                   'position' => [
                       'lat' => -33.925,
                       'lon' => 18.424,
                   ],
                   'poi' => [
                       'name' => 'Test Location',
                   ],
               ],
           ],
        ];
    }

    public function testBasicAddressSearch()
    {
        Http::fake(['*' => Http::response($this->mockResponse, 200)]);

        $result = json_decode(
            $this->service->searchAddress('123 Main Street')
               ->get(),
            true
        );

        $this->assertBasicResponseStructure($result);
        Http::assertSent(function ($request) {
            return $request['query'] === '123 Main Street'
               && $request['limit'] === 5;
        });
    }

    public function testAddressSearchWithCustomLimit()
    {
        Http::fake(['*' => Http::response($this->mockResponse, 200)]);

        $result = json_decode(
            $this->service->searchAddress('123 Main Street')
               ->limit(10)
               ->get(),
            true
        );

        Http::assertSent(function ($request) {
            return $request['limit'] === 10;
        });
    }

    public function testAddressSearchWithSingleCountry()
    {
        Http::fake(['*' => Http::response($this->mockResponse, 200)]);

        $this->service->searchAddress('123 Main Street')
           ->country('ZA')
           ->get();

        Http::assertSent(function ($request) {
            return $request['countrySet'] === 'ZA';
        });
    }

    public function testAddressSearchWithMultipleCountries()
    {
        Http::fake(['*' => Http::response($this->mockResponse, 200)]);

        $this->service->searchAddress('123 Main Street')
           ->country(['ZA', 'NA'])
           ->get();

        Http::assertSent(function ($request) {
            return $request['countrySet'] === 'ZA,NA';
        });
    }

    public function testAddressSearchWithLocation()
    {
        Http::fake(['*' => Http::response($this->mockResponse, 200)]);

        $this->service->searchAddress('123 Main Street')
           ->location(-33.925, 18.424, 5000)
           ->get();

        Http::assertSent(function ($request) {
            return $request['lat'] === -33.925
               && $request['lon'] === 18.424
               && $request['radius'] === 5000;
        });
    }

    public function testSchoolSearch()
    {
        Http::fake(['*' => Http::response($this->mockResponse, 200)]);

        $this->service->searchSchools('Cape Town High')
           ->limit(5)
           ->get();

        Http::assertSent(function ($request) {
            return $request['categorySet'] === '7372';
        });
    }

    public function testSearchWithError()
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->expectException(AzureMapsException::class);
        $this->service->searchAddress('Test')->get();
    }

    public function testSearchWithNoResults()
    {
        Http::fake(['*' => Http::response([
           'summary' => ['numResults' => 0],
           'results' => []
        ], 200)]);

        $result = json_decode(
            $this->service->searchAddress('NonexistentAddress')->get(),
            true
        );

        $this->assertEmpty($result);
    }

    private function assertBasicResponseStructure($result)
    {
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('address', $result[0]);
        $this->assertArrayHasKey('coordinates', $result[0]);
    }
}
