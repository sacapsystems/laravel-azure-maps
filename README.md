# Laravel Azure Maps

![Code Checks](https://github.com/sacapsystems/laravel-azure-maps/actions/workflows/code-checks.yaml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-%5E7.2-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-%5E6.0-FF2D20?logo=laravel)
![Code Style](https://img.shields.io/badge/Code%20Style-PSR--12-green)

A Laravel package for Azure Maps integration.

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Response Format](#response-format)
- [Package Development](#package-development)
  - [Testing](#testing)
  - [Code Style](#code-style)

## Requirements

This package requires:

- PHP 7.2 or higher
- Laravel 6.0 or higher
- An active Azure Maps subscription
- A valid Azure Maps API key

## Installation

First, add the repository to your composer.json:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/sacapsystems/laravel-azure-maps.git"
    }
]
```

Then, install the package via composer:

```bash
composer require sacapsystems/laravel-azure-maps
```

If you're not using package discovery, add the service provider and facade to your `config/app.php`:

```php
'providers' => [
    // ...
    Sacapsystems\LaravelAzureMaps\LaravelAzureMapsServiceProvider::class,
],

'aliases' => [
    // ...
    'AzureMaps' => Sacapsystems\LaravelAzureMaps\Facades\AzureMaps::class,
]
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="Sacapsystems\LaravelAzureMaps\LaravelAzureMapsServiceProvider"
```

Add your Azure Maps key to your .env file:

```
AZURE_MAPS_API_KEY=your-key-here
```

## Usage

```php
use Sacapsystems\LaravelAzureMaps\Facades\AzureMaps;

// Get coordinates for an address (default limit is 5)
$coordinates = AzureMaps::searchAddress('123 Main Street, Cape Town')
            ->get();

// Get coordinates with custom limit
$coordinates = AzureMaps::searchAddress('123 Main Street, Cape Town')
            ->limit(3)
            ->get();

// Search multiple countries
$results = AzureMaps::searchAddress('123 Main Street')
            ->country(['ZA', 'NA', 'BW'])
            ->get();

// Search for schools (default limit is 5)
$schools = AzureMaps::searchSchools('Cape Town High School')
            ->get();

// Search for schools with custom limit
$schools = AzureMaps::searchSchools('Cape Town High School')
            ->limit(10)
            ->get();
```

## Response Format
The search results will be returned in the following format:

```json
{
    "name": "Address Name",
    "address": {
        "line1": "123 Street Name",
        "line2": "Suburb, City",
        "suburb": "Suburb Name",
        "city": "City Name",
        "postalCode": "1234",
        "province": "Province Name",
        "provinceCode": "PR",
        "country": "Country Name",
        "countryCodeISO3": "CNT"
    },
    "coordinates": {
        "lat": -33.123456,
        "lng": 18.123456
    }
}
```

## Package Development

The following sections are for package developers only.

### Testing
Run the package tests with:

```bash
composer test
```

### Code Style
This package follows PSR-12 coding standards. Package developers can check and fix the code style with:

```bash
# Check code style
./vendor/bin/phpcs

# Fix code style
./vendor/bin/phpcbf
```

Or use composer scripts:

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```
