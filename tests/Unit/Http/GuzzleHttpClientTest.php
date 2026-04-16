<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JsonException;
use Natedaly\DummyjsonClient\Exception\ApiConnectionException;
use Natedaly\DummyjsonClient\Exception\ApiHttpException;
use Natedaly\DummyjsonClient\Exception\ApiNotFoundException;
use Natedaly\DummyjsonClient\Exception\InvalidResponseException;
use Natedaly\DummyjsonClient\Http\GuzzleHttpClient;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GuzzleHttpClientTest extends TestCase
{
    /**
     * @throws JsonException
     */
    #[Test]
    public function it_sends_a_get_request_and_returns_a_decoded_array(): void
    {
        $container = [];

        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                (string) json_encode([
                    'id' => 1,
                    'firstName' => 'Emily',
                    'lastName' => 'Johnson',
                    'email' => 'emily.johnson@x.dummyjson.com',
                ], JSON_THROW_ON_ERROR),
            ),
        ]);

        $history = Middleware::history($container);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $client = new Client([
            'base_uri' => 'https://dummyjson.com',
            'handler' => $stack,
        ]);

        $httpClient = new GuzzleHttpClient($client);

        $response = $httpClient->get('/users/1');

        $this->assertSame([
            'id' => 1,
            'firstName' => 'Emily',
            'lastName' => 'Johnson',
            'email' => 'emily.johnson@x.dummyjson.com',
        ], $response);

        $this->assertCount(1, $container);
        $this->assertSame('GET', $container[0]['request']->getMethod());
        $this->assertSame('/users/1', $container[0]['request']->getUri()->getPath());
        $this->assertSame('dummyjson.com', $container[0]['request']->getHeaderLine('Host'));
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_sends_query_parameters_with_get_requests(): void
    {
        $container = [];

        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                (string) json_encode([
                    'users' => [],
                    'total' => 208,
                    'skip' => 0,
                    'limit' => 2,
                ], JSON_THROW_ON_ERROR),
            ),
        ]);

        $history = Middleware::history($container);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $client = new Client([
            'base_uri' => 'https://dummyjson.com',
            'handler' => $stack,
        ]);

        $httpClient = new GuzzleHttpClient($client);

        $response = $httpClient->get('/users', [
            'limit' => 2,
            'skip' => 0,
        ]);

        $this->assertSame([
            'users' => [],
            'total' => 208,
            'skip' => 0,
            'limit' => 2,
        ], $response);

        $this->assertCount(1, $container);

        $request = $container[0]['request'];

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/users', $request->getUri()->getPath());
        $this->assertSame('limit=2&skip=0', $request->getUri()->getQuery());
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_sends_a_post_request_with_a_json_payload_and_returns_a_decoded_array(): void
    {
        $container = [];

        $mock = new MockHandler([
            new Response(
                201,
                ['Content-Type' => 'application/json'],
                (string) json_encode([
                    'id' => 209,
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'email' => 'jane@example.com',
                ], JSON_THROW_ON_ERROR),
            ),
        ]);

        $history = Middleware::history($container);

        $stack = HandlerStack::create($mock);
        $stack->push($history);

        $client = new Client([
            'base_uri' => 'https://dummyjson.com',
            'handler' => $stack,
        ]);

        $httpClient = new GuzzleHttpClient($client);

        $response = $httpClient->post('/users/add', [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
        ]);

        $this->assertSame(209, $response['id']);

        $this->assertCount(1, $container);

        $request = $container[0]['request'];

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/users/add', $request->getUri()->getPath());

        $this->assertSame(
            '{"firstName":"Jane","lastName":"Smith","email":"jane@example.com"}',
            (string) $request->getBody(),
        );
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_throws_an_api_exception_when_the_remote_api_returns_an_a_404(): void
    {
        $mock = new MockHandler([
            new Response(
                404,
                ['Content-Type' => 'application/json'],
                (string) json_encode([
                    'message' => 'User not found',
                ], JSON_THROW_ON_ERROR),
            ),
        ]);

        $stack = HandlerStack::create($mock);

        $client = new Client([
            'base_uri' => 'https://dummyjson.com',
            'handler' => $stack,
        ]);

        $httpClient = new GuzzleHttpClient($client);

        $this->expectException(ApiHttpException::class);
        $this->expectExceptionMessage('Not Found');

        $httpClient->get('/users/999');
    }

    #[Test]
    public function it_throws_an_invalid_response_exception_when_json_is_invalid(): void
    {
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"id":1,"firstName":"Emily"',
            ),
        ]);

        $stack = HandlerStack::create($mock);

        $client = new Client([
            'base_uri' => 'https://dummyjson.com',
            'handler' => $stack,
        ]);

        $httpClient = new GuzzleHttpClient($client);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('The remote API returned invalid JSON.');

        $httpClient->get('/users/1');
    }

    #[Test]
    public function it_wraps_transport_exceptions_in_an_api_exception(): void
    {
        $mock = new MockHandler([
            new ConnectException(
                'Connection timed out',
                new Request('GET', '/users/1'),
            ),
        ]);

        $stack = HandlerStack::create($mock);

        $client = new Client([
            'base_uri' => 'https://dummyjson.com',
            'handler' => $stack,
        ]);

        $httpClient = new GuzzleHttpClient($client);

        $this->expectException(ApiConnectionException::class);
        $this->expectExceptionMessage('Connection timed out');

        $httpClient->get('/users/1');
    }

    #[Test]
    public function it_throws_an_invalid_response_exception_when_the_decoded_payload_is_not_an_array(): void
    {
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '"just-a-string"',
            ),
        ]);

        $stack = HandlerStack::create($mock);

        $client = new Client([
            'base_uri' => 'https://dummyjson.com',
            'handler' => $stack,
        ]);

        $httpClient = new GuzzleHttpClient($client);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('The remote API returned an unexpected payload.');

        $httpClient->get('/users/1');
    }
}