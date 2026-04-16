<?php

declare(strict_types=1);

namespace Natedaly\DummyjsonClient\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use http\Exception\RuntimeException;
use JsonException;
use Natedaly\DummyjsonClient\Contracts\HttpClient;
use Natedaly\DummyjsonClient\Exception\ApiConnectionException;
use Natedaly\DummyjsonClient\Exception\ApiHttpException;
use Natedaly\DummyjsonClient\Exception\InvalidResponseException;
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
            $this->matchHttpException($exception);
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
            throw new ApiHttpException('Failed to call remote API.', 0, $exception);
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
            throw new InvalidResponseException('The remote API returned invalid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidResponseException('The remote API returned an unexpected payload.');
        }

        // Just in case my exception matcher missed any above 400.
        if ($statusCode >= 400) {
            throw new ApiHttpException(sprintf('Remote API returned HTTP %d.', $statusCode), $statusCode);
        }

        return $decoded;
    }

    private function matchHttpException(Throwable $exception): void
    {
        match (true) {
            $exception instanceof ConnectException => throw new ApiConnectionException(
                $exception->getMessage(),
                0,
                $exception,
            ),

            $exception instanceof RequestException && $exception->hasResponse() => throw new ApiHttpException(
                $exception->getResponse()->getReasonPhrase(),
                $exception->getResponse()->getStatusCode(),
                $exception,
            ),

            $exception instanceof GuzzleException => throw new ApiHttpException(
                'Failed to call remote API.',
                0,
                $exception,
            ),

            default => throw new RuntimeException(
                $exception->getMessage(),
                0,
                $exception,
            ),
        };
    }
}