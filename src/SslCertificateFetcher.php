<?php declare(strict_types=1);

namespace AlanVdb\HttpClient;

use AlanVdb\HttpClient\Definition\SslCertificateFetcherInterface;
use Exception;

/**
 * Class SslCertificateFetcher
 *
 * Fetches the SSL certificate for a given domain.
 */
class SslCertificateFetcher implements SslCertificateFetcherInterface
{
    /**
     * Retrieves the SSL certificate for a given domain and optionally saves it to a file.
     *
     * @param string $domain The domain name for which to retrieve the SSL certificate.
     * @param string|null $savePath Optional. The path where the certificate should be saved.
     * @return string The SSL certificate in PEM format.
     * @throws Exception If the certificate cannot be retrieved or saved.
     */
    public function getCertificate(string $domain, ?string $savePath = null): string
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = $this->streamSocketClient("ssl://{$domain}:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

        if (!$client) {
            throw new Exception("Unable to connect to {$domain}: {$errstr} ({$errno})");
        }

        $params = stream_context_get_params($client);
        $certificate = $this->opensslX509Export($params['options']['ssl']['peer_certificate'], $certString);

        if ($certificate) {
            if ($savePath !== null) {
                $this->saveCertificateToFile($certString, $savePath);
            }
            return $certString;
        }

        throw new Exception("Failed to retrieve the certificate for {$domain}");
    }

    /**
     * Saves the SSL certificate to a file.
     *
     * @param string $certString The certificate in PEM format.
     * @param string $savePath The path where the certificate should be saved.
     * @throws Exception If the certificate cannot be saved.
     */
    private function saveCertificateToFile(string $certString, string $savePath): void
    {
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            if (!$this->createDirectory($directory) && !is_dir($directory)) {
                throw new Exception("Failed to create directory: {$directory}");
            }
        }

        if (!$this->saveToFile($savePath, $certString)) {
            throw new Exception("Failed to save the certificate to {$savePath}");
        }

        clearstatcache();
        if (!file_exists($savePath)) {
            throw new Exception("The certificate file was not found after saving to {$savePath}");
        }
    }

    /**
     * Creates a stream socket client connection.
     *
     * @param string $remote_socket The remote socket connection string.
     * @param int &$errno A reference to the error number.
     * @param string &$errstr A reference to the error message.
     * @param int $timeout Connection timeout.
     * @param int $flags Stream client flags.
     * @param resource $context Stream context resource.
     * @return resource|false The stream resource on success, or false on failure.
     */
    protected function streamSocketClient($remote_socket, &$errno, &$errstr, $timeout, $flags, $context)
    {
        return stream_socket_client($remote_socket, $errno, $errstr, $timeout, $flags, $context);
    }

    /**
     * Exports an SSL certificate to a string in PEM format.
     *
     * @param mixed $x509 The X.509 certificate resource.
     * @param string &$output A reference to the output string.
     * @return bool Returns true on success, or false on failure.
     */
    protected function opensslX509Export($x509, &$output): bool
    {
        return openssl_x509_export($x509, $output);
    }

    /**
     * Creates a directory recursively.
     *
     * @param string $directory The directory path.
     * @return bool Returns true on success, or false on failure.
     */
    protected function createDirectory(string $directory): bool
    {
        return mkdir($directory, 0777, true);
    }

    /**
     * Saves content to a file.
     *
     * @param string $path The path to the file.
     * @param string $content The content to save.
     * @return bool Returns true on success, or false on failure.
     */
    protected function saveToFile(string $path, string $content): bool
    {
        return file_put_contents($path, $content) !== false;
    }
}
