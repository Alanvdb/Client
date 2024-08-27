<?php declare(strict_types=1);

namespace AlanVdb\HttpClient\Factory;

use Psr\Http\Client\ClientInterface;
use AlanVdb\HttpClient\Definition\HttpClientFactoryInterface;
use AlanVdb\HttpClient\Definition\SslCertificateFetcherInterface;
use AlanVdb\HttpClient\HttpClient;
use AlanVdb\HttpClient\SslCertificateFetcher;

class HttpClientFactory implements HttpClientFactoryInterface
{
    /**
     * Creates and returns an instance of HttpClient.
     *
     * @param string $sslFilesDirectory The directory where SSL certificates will be stored.
     * @param SslCertificateFetcherInterface|null $customFetcher Optional custom SSL certificate fetcher. If null, a default fetcher will be used.
     * @return ClientInterface The created HttpClient instance.
     */
    public function createHttpClient(string $sslFilesDirectory, SslCertificateFetcherInterface $customFetcher = null): ClientInterface
    {
        return new HttpClient($sslFilesDirectory, $customFetcher ?? $this->createSslCertificateFetcher());
    }

    /**
     * Creates and returns an instance of SslCertificateFetcherInterface.
     *
     * @return SslCertificateFetcherInterface The created SslCertificateFetcher instance.
     */
    public function createSslCertificateFetcher(): SslCertificateFetcherInterface
    {
        return new SslCertificateFetcher();
    }
}
