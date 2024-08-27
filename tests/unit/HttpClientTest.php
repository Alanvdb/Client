<?php

namespace AlanVdb\HttpClient\Tests;

use AlanVdb\HttpClient\HttpClient;
use AlanVdb\HttpClient\Exception\HttpClientException;
use AlanVdb\HttpClient\SslCertificateFetcher;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

#[CoversClass(HttpClient::class)]
class HttpClientTest extends TestCase
{
    /**
     * Tests that an exception is thrown when the request fails.
     */
    public function testSendRequestThrowsException()
    {
        $client = new HttpClient(__DIR__ . '/../../assets/ssl');
        $request = new Request('GET', 'http://invalid.domain.example');

        $warnings = [];
        set_error_handler(function ($errno, $errstr) use (&$warnings) {
            $warnings[] = $errstr;
        });

        try {
            $this->expectException(HttpClientException::class);
            $client->sendRequest($request);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Tests that the HttpClient handles RequestException correctly.
     */
    public function testSendRequestHandlesRequestException()
    {
        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_WARNING);

        $guzzleMock = $this->createMock(Client::class);
        $guzzleMock->method('send')
            ->will($this->throwException(new RequestException('Error Communicating with Server', new Request('GET', 'https://google.com'))));

        $client = new HttpClient(__DIR__ . '/../../assets/ssl');

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($client, $guzzleMock);

        $request = new Request('GET', 'https://google.com');

        $this->expectException(HttpClientException::class);

        try {
            $client->sendRequest($request);
        } finally {
            error_reporting($previousErrorReporting);
        }
    }

    /**
     * Tests that the directory for storing certificates is created if it doesn't exist.
     */
    public function testEnsureCertificateCreatesDirectory()
    {
        // Utilisation de la vraie classe SslCertificateFetcher
        $fetcher = new SslCertificateFetcher();
        
        // On crée un HttpClient avec le fetcher réel
        $client = new HttpClient(__DIR__ . '/../../assets/ssl', $fetcher);
        
        $domain = 'archive.org';
        $certPath = dirname(dirname(__DIR__)) . "/assets/ssl/{$domain}.pem";
        
        // On essaie de résoudre le chemin réel du répertoire
        $certDir = realpath(dirname($certPath)) ?: dirname($certPath);

        // Supprime le répertoire avant le test pour s'assurer qu'il sera recréé
        $this->removeDirectory($certDir);
        
        // Appel de la méthode pour récupérer et enregistrer le certificat
        $this->invokePrivateMethod($client, 'ensureCertificate', [$domain]);
        
        // Vérification immédiate après la création
        clearstatcache();  // On s'assure que le cache de PHP est vidé
        
        // Assertions pour vérifier que le répertoire et le fichier existent bien
        $this->assertDirectoryExists($certDir, "Le répertoire pour les certificats SSL n'a pas été créé : {$certDir}");
        $this->assertFileExists($certPath, "Le fichier de certificat SSL n'a pas été créé : {$certPath}");
    }
    
    

    /**
     * Invokes a private method on an object.
     *
     * @param object $object The object on which to invoke the method.
     * @param string $methodName The name of the method to invoke.
     * @param array $parameters The parameters to pass to the method.
     * @return mixed The return value of the invoked method.
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Recursively removes a directory and its contents.
     *
     * @param string $dir The directory to remove.
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }

    public function testEnsureCertificateThrowsExceptionOnFailedDirectoryCreation()
    {
        $unwritableDir = 'C:/Windows/System32/ssl_test_dir';
        
        // Utilisez la classe réelle pour HttpClient
        $fetcher = new SslCertificateFetcher();
        $client = new HttpClient($unwritableDir, $fetcher);
    
        // Attendez-vous à ce qu'une exception HttpClientException soit lancée
        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage("Failed to create directory");
    
        // Appel direct de la méthode ensureCertificate
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('ensureCertificate');
        $method->setAccessible(true);
        $method->invokeArgs($client, ['example.com']);
    }
        
}
