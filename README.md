# AlanVdb / HTTP Client

## Overview

`alanvdb/http-client` is a PSR-18 compliant HTTP client library that automatically handles SSL certificate retrieval for a given domain. It is built on top of [Guzzle](https://github.com/guzzle/guzzle) and is designed to simplify HTTP requests while ensuring secure connections by managing SSL certificates automatically.

## Features

- **PSR-18 Compliant**: Integrates seamlessly with any PSR-18 HTTP client interface implementation.
- **Automatic SSL Certificate Retrieval**: Automatically fetches and stores SSL certificates for specified domains.
- **Customizable SSL Certificate Fetcher**: Easily customize the SSL certificate fetcher or extend it to meet specific needs.
- **Factory Pattern**: The `HttpClientFactory` allows for easy instantiation and dependency injection.
- **Built on Guzzle**: Leverages Guzzle's powerful HTTP client capabilities.
- **Easy to Use**: Simple API for making HTTP requests and handling SSL certificates.

## Installation

You can install this package via Composer:

```bash
composer require alanvdb/http-client
```

For development purposes, you can install the development dependencies as well:

```bash
composer install --dev
```

## Usage

### Basic Usage

Here is an example of how to use the `HttpClient` to send a GET request to a domain:

```php
require 'vendor/autoload.php';

use AlanVdb\HttpClient\Factory\HttpClientFactory;
use GuzzleHttp\Psr7\Request;

// Directory where SSL certificates will be stored
$directoryPath = __DIR__ . '/path/to/ssl/certificates';

$factory = new HttpClientFactory();
$client = $factory->createHttpClient($directoryPath);

$request = new Request('GET', 'https://example.com');
$response = $client->sendRequest($request);

echo $response->getBody();
```

### Handling SSL Certificates

The `HttpClient` automatically fetches the SSL certificate for the domain specified in the request and saves it to the directory you provide. This ensures secure connections without manual certificate management.

### Custom SSL Certificate Fetcher

You can also customize the SSL certificate fetching process by providing your implementation of the `SslCertificateFetcherInterface`:

```php
use AlanVdb\HttpClient\Factory\HttpClientFactory;
use AlanVdb\HttpClient\Definition\SslCertificateFetcherInterface;

class CustomSslCertificateFetcher implements SslCertificateFetcherInterface
{
    // Implement the required methods here
}

$directoryPath = __DIR__ . '/path/to/ssl/certificates';
$customFetcher = new CustomSslCertificateFetcher();
$factory = new HttpClientFactory();
$client = $factory->createHttpClient($directoryPath, $customFetcher);
```

### Using the Factory Pattern

The `HttpClientFactory` provides a convenient way to create an `HttpClient` instance:

```php
use AlanVdb\HttpClient\Factory\HttpClientFactory;

$directoryPath = __DIR__ . '/path/to/ssl/certificates';
$factory = new HttpClientFactory();
$client = $factory->createHttpClient($directoryPath);
```

## Running Tests

To run the tests, you need to install the development dependencies first:

```bash
composer install --dev
```

Then, you can run the tests using PHPUnit:

```bash
vendor/bin/phpunit --testdox
```

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
