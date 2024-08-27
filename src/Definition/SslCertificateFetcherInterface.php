<?php declare(strict_types=1);

namespace AlanVdb\HttpClient\Definition;

interface SslCertificateFetcherInterface
{
    /**
     * Retrieves the SSL certificate for a given domain.
     *
     * @param string $domain The domain name for which to retrieve the SSL certificate.
     * @return string The SSL certificate in PEM format.
     * @throws Exception If the certificate cannot be retrieved.
     */
    public function getCertificate(string $domain): string;
}
