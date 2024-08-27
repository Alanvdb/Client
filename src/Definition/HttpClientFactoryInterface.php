<?php declare(strict_types=1);

namespace AlanVdb\HttpClient\Definition;

use Psr\Http\Client\ClientInterface;

interface HttpClientFactoryInterface
{
    /**
     * Creates and returns an instance of HttpClient.
     *
     * @param string $sslFilesDirectory The directory where SSL certificates will be stored.
     * @param SslCertificateFetcherInterface|null $customFetcher Optional custom SSL certificate fetcher.
     * @return ClientInterface The created HttpClient instance.
     */
    public function createHttpClient(string $sslFilesDirectory, SslCertificateFetcherInterface $customFetcher = null): ClientInterface;

    /**
     * Creates and returns an instance of SslCertificateFetcherInterface.
     *
     * @return SslCertificateFetcherInterface The created SslCertificateFetcher instance.
     */
    public function createSslCertificateFetcher(): SslCertificateFetcherInterface;
}
