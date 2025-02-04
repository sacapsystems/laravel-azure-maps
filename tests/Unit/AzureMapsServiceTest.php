<?php

namespace Sacapsystems\LaravelAzureMaps\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Sacapsystems\LaravelAzureMaps\Exceptions\AzureMapsException;
use Sacapsystems\LaravelAzureMaps\Services\AzureMapsService;
use Sacapsystems\LaravelAzureMaps\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Sacapsystems\LaravelAzureMaps\Builders\QueryBuilder;

class AzureMapsServiceTest extends TestCase
{
    protected $service;
    protected $mockResponse;
    protected $container = [];
    protected $mockHandler;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('azure-maps.base_url', 'https://atlas.microsoft.com/search/fuzzy/json');
        Config::set('azure-maps.api_key', 'test-key');

        // Set up Guzzle mock
        $this->container = [];
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $history = Middleware::history($this->container);
        $handlerStack->push($history);
        $this->client = new Client(['handler' => $handlerStack]);

        // Create service with mocked query builder factory
        $this->service = new AzureMapsService(function () {
            return new QueryBuilder(
                Config::get('azure-maps.base_url'),
                Config::get('azure-maps.api_key'),
                $this->client
            );
        });
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
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->mockResponse))
        );

        $result = json_decode(
            $this->service->searchAddress('123 Main Street')
                ->get(),
            true
        );

        $this->assertBasicResponseStructure($result);
        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertEquals('123 Main Street', $query['query']);
        $this->assertEquals(5, $query['limit']);
    }

    public function testAddressSearchWithCustomLimit()
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->mockResponse))
        );

        $result = json_decode(
            $this->service->searchAddress('123 Main Street')
                ->limit(10)
                ->get(),
            true
        );

        $request = $this->container[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertEquals(10, $query['limit']);
    }

    public function testAddressSearchWithSingleCountry()
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->mockResponse))
        );

        $this->service->searchAddress('123 Main Street')
            ->country('ZA')
            ->get();

        $request = $this->container[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertEquals('ZA', $query['countrySet']);
    }

    public function testAddressSearchWithMultipleCountries()
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->mockResponse))
        );

        $this->service->searchAddress('123 Main Street')
            ->country(['ZA', 'NA'])
            ->get();

        $request = $this->container[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertEquals('ZA,NA', $query['countrySet']);
    }

    public function testAddressSearchWithLocation()
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->mockResponse))
        );

        $this->service->searchAddress('123 Main Street')
            ->location(-33.925, 18.424, 5000)
            ->get();

        $request = $this->container[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertEquals(-33.925, $query['lat']);
        $this->assertEquals(18.424, $query['lon']);
        $this->assertEquals(5000, $query['radius']);
    }

    public function testSchoolSearch()
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode($this->mockResponse))
        );

        $this->service->searchSchools('Cape Town High')
            ->limit(5)
            ->get();

        $request = $this->container[0]['request'];
        $query = [];
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertEquals('7372', $query['categorySet']);
    }

    public function testSearchWithError()
    {
        $this->mockHandler->append(new Response(500));

        $this->expectException(AzureMapsException::class);
        $this->service->searchAddress('Test')->get();
    }

    public function testSearchWithNoResults()
    {
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'summary' => ['numResults' => 0],
                'results' => []
            ]))
        );

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
