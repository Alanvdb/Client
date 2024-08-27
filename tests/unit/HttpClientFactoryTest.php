<?php

namespace AlanVdb\HttpClient\Tests;

use AlanVdb\HttpClient\Factory\HttpClientFactory;
use AlanVdb\HttpClient\Definition\SslCertificateFetcherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class HttpClientFactoryTest extends TestCase
{
    public function testCreateHttpClientReturnsClientInterface()
    {
        $factory = new HttpClientFactory();
        $client = $factory->createHttpClient('/path/to/ssl/certs');

        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    public function testCreateHttpClientWithCustomFetcher()
    {
        $customFetcher = $this->createMock(SslCertificateFetcherInterface::class);

        $factory = new HttpClientFactory();
        $client = $factory->createHttpClient('/path/to/ssl/certs', $customFetcher);

        $this->assertInstanceOf(ClientInterface::class, $client);

        // Optionally, check if the client is using the custom fetcher
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('sslCertificateFetcher');
        $property->setAccessible(true);
        $this->assertSame($customFetcher, $property->getValue($client));
    }
}
