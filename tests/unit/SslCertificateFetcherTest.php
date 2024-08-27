<?php

namespace AlanVdb\HttpClient\Tests;

use AlanVdb\HttpClient\SslCertificateFetcher;
use PHPUnit\Framework\TestCase;

#[CoversClass(SslCertificateFetcher::class)]
class SslCertificateFetcherTest extends TestCase
{
    /**
     * Tests that a certificate can be successfully retrieved for a valid domain.
     */
    public function testCanRetrieveCertificate()
    {
        $fetcher = new SslCertificateFetcher();
        $domain = 'example.com';

        $certificate = $fetcher->getCertificate($domain);

        $this->assertIsString($certificate);
        $this->assertNotEmpty($certificate);
        $this->assertStringContainsString('BEGIN CERTIFICATE', $certificate);
    }

    /**
     * Tests that a certificate can be successfully retrieved and saved for a valid domain.
     */
    public function testCanRetrieveAndSaveCertificate()
    {
        $fetcher = new SslCertificateFetcher();
        $domain = 'example.com';
        $savePath = dirname(__DIR__) . "/../assets/ssl/{$domain}.pem";

        if (file_exists($savePath)) {
            unlink($savePath);
        }

        $fetcher->getCertificate($domain, $savePath);

        $this->assertFileExists($savePath);

        $savedCertificate = file_get_contents($savePath);
        $this->assertIsString($savedCertificate);
        $this->assertNotEmpty($savedCertificate);
        $this->assertStringContainsString('BEGIN CERTIFICATE', $savedCertificate);

        unlink($savePath);
    }

    /**
     * Tests that an exception is thrown when failing to retrieve a certificate.
     */
    public function testGetCertificateFailsToRetrieveCertificate()
    {
        $fetcherMock = $this->getMockBuilder(SslCertificateFetcher::class)
            ->onlyMethods(['streamSocketClient', 'opensslX509Export'])
            ->getMock();
    
        $fakeStream = fopen('php://temp', 'r+');
        $fetcherMock->method('streamSocketClient')->willReturn($fakeStream);
        $fetcherMock->method('opensslX509Export')->willReturn(false);
    
        stream_context_set_params($fakeStream, [
            'options' => [
                'ssl' => [
                    'peer_certificate' => 'dummy_certificate'
                ]
            ]
        ]);
    
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Failed to retrieve the certificate for google.com");
    
        $fetcherMock->getCertificate('google.com');
    
        fclose($fakeStream);
    }

    /**
     * Tests that an exception is thrown for an invalid domain.
     */
    public function testThrowsExceptionForInvalidDomain()
    {
        $this->expectException(\Exception::class);

        set_error_handler(fn() => true);
    
        try {
            $fetcher = new SslCertificateFetcher();
            $invalidDomain = 'invalid.domain.example';
            $fetcher->getCertificate($invalidDomain);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Tests that an exception is thrown when directory creation fails.
     */
    public function testSaveCertificateToFileFailsToCreateDirectory()
    {
        $fetcher = new SslCertificateFetcher();
        $unwritableDir = 'C:/Windows/System32/ssl_test_dir';
        $certPath = $unwritableDir . '/example.com.pem';
    
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create directory');
    
        $fetcher->getCertificate('example.com', $certPath);
    }

    /**
     * Tests that an exception is thrown when saving the certificate fails.
     */
    public function testSaveCertificateToFileFailsToWriteFile()
    {
        $fetcher = $this->getMockBuilder(SslCertificateFetcher::class)
                        ->onlyMethods(['saveToFile'])
                        ->getMock();
    
        $fetcher->expects($this->once())
                ->method('saveToFile')
                ->willReturn(false);
    
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to save the certificate to');
    
        $fetcher->getCertificate('example.com', '/path/to/existing/dir/example.com.pem');
    }

    /**
     * Tests that an exception is thrown when the certificate file is not found after saving.
     */
    public function testSaveCertificateToFileFailsToFindFile()
    {
        $fetcher = $this->getMockBuilder(SslCertificateFetcher::class)
                        ->onlyMethods(['saveToFile'])
                        ->getMock();

        $fetcher->expects($this->once())
                ->method('saveToFile')
                ->willReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The certificate file was not found after saving to');

        $fetcher->getCertificate('example.com', '/path/to/existing/dir/example.com.pem');
    }

    /**
     * Tests that the directory is created if it doesn't exist.
     */
    public function testCreateDirectoryIsCalled()
    {
        $fetcher = new SslCertificateFetcher();
        $testDir = sys_get_temp_dir() . '/ssl_test_dir';
        $certPath = $testDir . '/example.com.pem';
    
        if (is_dir($testDir)) {
            $this->removeDirectory($testDir);
        }
    
        $fetcher->getCertificate('example.com', $certPath);
    
        $this->assertDirectoryExists($testDir, "The directory {$testDir} should have been created.");
    
        $this->removeDirectory($testDir);
    }

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
}
