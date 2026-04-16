<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Exceptions\ApiAuthenticationException;
use Natedaly\DummyjsonClient\Exceptions\ApiNotFoundException;
use Natedaly\DummyjsonClient\Exceptions\ApiRateLimitException;
use Natedaly\DummyjsonClient\Exceptions\ApiRequestException;
use Natedaly\DummyjsonClient\Exceptions\ApiServerException;
use Natedaly\DummyjsonClient\Exceptions\ApiTransportException;
use Natedaly\DummyjsonClient\Exceptions\ApiValidationException;
use Natedaly\DummyjsonClient\Exceptions\InvalidApiResponseException;
use Throwable;

final readonly class GuzzleHttpClient implements HttpClient
{
    public function __construct(
        private ClientInterface $client,
    ) {
    }

    public static function make(
        string $baseUri,
        array $options = [],
    ): self {
        // Some sensible default that can be overridden.
        $client = new Client(array_replace([
            'base_uri' => $baseUri,
            'timeout' => 10.0,
            'connect_timeout' => 5.0,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ], $options));

        return new self($client);
    }

    public function get(string $uri, array $query = [], array $options = []): array
    {
        try {
            $response = $this->client->request('GET', $uri, array_replace(
                $options,
                ['query' => $query],
            ));
        } catch (GuzzleException $exception) {
            $this->matchTransportException($exception);
        }

        return $this->decodeResponse(
            $response->getStatusCode(),
            (string) $response->getBody(),
        );
    }

    public function post(string $uri, array $payload = [], array $options = []): array
    {
        try {
            $response = $this->client->request('POST', $uri, array_replace(
                $options,
                ['json' => $payload],
            ));
        } catch (GuzzleException $exception) {
            $this->matchTransportException($exception);
        }

        return $this->decodeResponse(
            $response->getStatusCode(),
            (string) $response->getBody(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(int $statusCode, string $body): array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidApiResponseException('The remote API returned invalid JSON.', null, $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidApiResponseException('The remote API returned an unexpected payload.');
        }

        if ($statusCode >= 400) {
            $this->throwForStatus($statusCode);
        }

        return $decoded;
    }

    private function throwForStatus(int $statusCode): never
    {
        $message = sprintf('Remote API returned HTTP %d.', $statusCode);

        throw match (true) {
            $statusCode === 401, $statusCode === 403 => new ApiAuthenticationException($message, $statusCode),
            $statusCode === 404 => new ApiNotFoundException($message, $statusCode),
            $statusCode === 422 => new ApiValidationException($message, $statusCode),
            $statusCode === 429 => new ApiRateLimitException($message, $statusCode),
            $statusCode >= 500 => new ApiServerException($message, $statusCode),
            default => new ApiRequestException($message, $statusCode),
        };
    }

    private function matchTransportException(Throwable $exception): never
    {
        throw match (true) {
            $exception instanceof ConnectException => new ApiTransportException(
                $exception->getMessage(),
                null,
                $exception,
            ),

            $exception instanceof RequestException && $exception->hasResponse() => $this->throwForStatus(
                $exception->getResponse()->getStatusCode(),
            ),

            default => new ApiTransportException(
                'Failed to call remote API.',
                null,
                $exception,
            ),
        };
    }
}
