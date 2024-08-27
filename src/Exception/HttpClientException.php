<?php

namespace AlanVdb\HttpClient\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

class HttpClientException
    extends RuntimeException
    implements ClientExceptionInterface
{}
