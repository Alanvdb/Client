<?php declare(strict_types=1);

namespace AlanVdb\HttpClient;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use AlanVdb\HttpClient\Definition\SslCertificateFetcherInterface;
use AlanVdb\HttpClient\Exception\HttpClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class HttpClient
 *
 * A PSR-18 compliant HTTP client that automatically handles SSL certificate retrieval.
 */
class HttpClient implements ClientInterface
{
    private Client $client;
    private SslCertificateFetcherInterface $sslCertificateFetcher;
    private string $directoryPath;

    /**
     * HttpClient constructor.
     *
     * @param string $directoryPath The path where SSL certificates should be stored.
     * @param SslCertificateFetcherInterface|null $sslCertificateFetcher Optional SSL certificate fetcher.
     */
    public function __construct(string $directoryPath, ?SslCertificateFetcherInterface $sslCertificateFetcher = null)
    {
        $this->client = new Client([
            'verify' => true,
            'allow_redirects' => true,
        ]);

        $this->sslCertificateFetcher = $sslCertificateFetcher ?? new SslCertificateFetcher();
        $this->directoryPath = rtrim($directoryPath, '/\\');
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $domain = parse_url((string) $request->getUri(), PHP_URL_HOST);
        $this->ensureCertificate($domain);

        try {
            return $this->client->send($request);
        } catch (RequestException $e) {
            throw new HttpClientException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function ensureCertificate(string $domain)
    {
        try {
            $certPath = $this->directoryPath . DIRECTORY_SEPARATOR . "{$domain}.pem";
            $certDir = dirname($certPath);

            if (!is_dir($certDir) && !mkdir($certDir, 0777, true) && !is_dir($certDir)) {
                throw new \Exception("Failed to create directory: {$certDir}");
            }

            if (!file_exists($certPath)) {
                $this->sslCertificateFetcher->getCertificate($domain, $certPath);
                clearstatcache();
            }

            $this->client = new Client([
                'verify' => $certPath,
                'allow_redirects' => true,
            ]);
        } catch (\Exception $e) {
            throw new HttpClientException("Error fetching SSL certificate for domain {$domain}: " . $e->getMessage(), 0, $e);
        }
    }
}
