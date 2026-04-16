# DummyJSON Client

A small, framework-independent PHP client for [DummyJSON](https://dummyjson.com/) user data.

The package is designed as a Composer library rather than a framework package. It can be used directly in plain PHP, or registered inside a DI container in Laravel, Symfony, Slim, Laminas, or any other PHP tooling that can bind interfaces to implementations.

## Requirements

- PHP 8.4+
- Composer
- Guzzle 7
- PSR-3 logger support through `psr/log`

## Installation

```bash
composer require natedaly/dummyjson-client
```

## Quick Start

```php
<?php

use Natedaly\DummyjsonClient\Client;

$client = Client::make('https://dummyjson.com');

$user = $client->users()->getById(1);

$users = $client
    ->users()
    ->get()
    ->limit(10)
    ->skip(0)
    ->select(['firstName', 'lastName', 'email'])
    ->fetch();

$created = $client->users()->create(
    firstName: 'Jane',
    lastName: 'Smith',
    email: 'jane@example.com',
);
```

## Architecture

The package is split into small layers with clear responsibilities:

- `Client` is the public entry point. It owns the configured HTTP client and exposes domain services such as `users()`.
- `Services\UserService` contains user-specific operations: fetch by id, list through a query object, and create.
- `Query\UserQuery` provides a fluent query builder for list operations, keeping pagination and field selection out of the service method signature.
- `Http\GuzzleHttpClient` adapts Guzzle to the package's own `HttpClient` contract.
- `Dto\UserDto` and `Dto\UserCollection` give callers typed return objects rather than raw arrays.
- `Contracts\*` define the injection surface for applications and tests.
- `Exceptions\*` convert transport, response, and HTTP errors into package-specific exception types.
- `Testing\*` fakes make it possible to test application code without calling DummyJSON.

The main design choice is to keep the core package framework-agnostic. There are no Laravel, Symfony, or container-specific assumptions inside the client. Frameworks can wire the package however they like, while the library stays plain PHP.

I started down the normal path of passing pagination parameters directly into the list method. While working through the API design, I decided a dedicated `UserQuery` object would make the consumer experience cleaner. It gives the package a fluent API for pagination and field selection, keeps the service interface small, and leaves room for future query options without turning one method into a long list of optional parameters.

## Technical Test Goals

This package was built for a job interview technical test, so I treated the brief as more than just "call an API". I wanted to show how I would design a small Composer package that remains testable, framework-agnostic, and predictable when the remote service behaves badly.

The key goals I kept in mind were:

- Remote APIs are often unstable and unreliable, so the package wraps HTTP failures, bad JSON, unexpected payloads, and non-2xx responses in explicit package exceptions. The main test suite uses offline mocked HTTP responses so it can still pass if DummyJSON is unavailable or its data changes.
- API errors should be clear to other developers using the package. Every package exception extends `DummyJsonException`, with more specific exceptions for authentication, not found, validation, rate limit, server, transport, request, and invalid response failures.
- Exceptions are caught at the package boundary where useful context can be added, then re-thrown as verbose domain-specific errors for the consumer to handle. This avoids leaking low-level Guzzle exceptions into application code.
- Logging is optional but injectable. Consumers can pass any PSR-3 logger, including Monolog or a framework logger, and receive useful context when user fetch, list, or create operations fail.
- Unit tests do not depend on the live remote API. The user service is tested through the `HttpClient` interface, and the Guzzle adapter is tested with Guzzle's mock handler so request construction, response decoding, and error mapping are covered without live network calls.

## Dependency Injection

The package leans on interfaces so it can be injected into any framework DI container.

The main application-facing contract is:

```php
use Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface;
```

The HTTP boundary is also abstracted:

```php
use Natedaly\DummyjsonClient\Contracts\HttpClient;
```

That means your application can depend on `DummyJsonClientInterface`, while this package can use the default Guzzle implementation internally. In tests, you can swap the real client for `Testing\FakeDummyJsonClient`, or provide your own implementation of the same contracts.

## Logging

Logging is optional and PSR-3 compatible.

By default, `Client::make()` uses `Psr\Log\NullLogger`, so plain PHP usage is silent and does not require any logger setup.

You can inject your own custom logger by passing any `Psr\Log\LoggerInterface` implementation. Monolog works out of the box, but so will a framework logger or a custom in-house logger.

```php
use Natedaly\DummyjsonClient\Client;

$client = Client::make('https://dummyjson.com', logger: $monolog);
```

User service failures are logged with context such as user id, pagination values, selected fields, exception class, message, and HTTP status code where available.

## Domain Exceptions

All package exceptions extend:

```php
Natedaly\DummyjsonClient\Exceptions\DummyJsonException
```

This makes errors from this Composer package quick to spot in logs, queues, jobs, and exception trackers, even when they are mixed into a sea of unrelated application errors.

Specific exception types include:

- `ApiAuthenticationException` for 401 and 403 responses.
- `ApiNotFoundException` for 404 responses.
- `ApiValidationException` for 422 responses.
- `ApiRateLimitException` for 429 responses.
- `ApiServerException` for 5xx responses.
- `ApiRequestException` for other HTTP request failures.
- `ApiTransportException` for network and transport failures.
- `InvalidApiResponseException` for invalid JSON or unexpected response payloads.

Each `DummyJsonException` can expose the HTTP status code through:

```php
$exception->statusCode();
```

Example:

```php
use Natedaly\DummyjsonClient\Exceptions\DummyJsonException;

try {
    $user = $client->users()->getById(999);
} catch (DummyJsonException $exception) {
    report($exception);

    $statusCode = $exception->statusCode();
}
```

## Framework And Tooling Examples

### Plain PHP, Silent By Default

```php
use Natedaly\DummyjsonClient\Client;

$client = Client::make('https://dummyjson.com');
```

### Plain PHP With A PSR Logger

```php
use Natedaly\DummyjsonClient\Client;

$client = Client::make('https://dummyjson.com', logger: $monolog);
```

### Laravel Service Provider

```php
use Natedaly\DummyjsonClient\Client;
use Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface;
use Psr\Log\LoggerInterface;

$this->app->bind(
    DummyJsonClientInterface::class,
    fn () => Client::make(
        'https://dummyjson.com',
        logger: app(LoggerInterface::class),
    ),
);
```

You can then type-hint the interface anywhere Laravel resolves dependencies:

```php
use Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface;

final readonly class ImportUsers
{
    public function __construct(
        private DummyJsonClientInterface $dummyJson,
    ) {}
}
```

### Symfony Services

```yaml
services:
  Natedaly\DummyjsonClient\Http\GuzzleHttpClient:
    factory: ['Natedaly\DummyjsonClient\Http\GuzzleHttpClient', 'make']
    arguments:
      $baseUri: 'https://dummyjson.com'

  Natedaly\DummyjsonClient\Client:
    arguments:
      $httpClient: '@Natedaly\DummyjsonClient\Http\GuzzleHttpClient'
      $logger: '@logger'

  Natedaly\DummyjsonClient\Contracts\DummyJsonClientInterface:
    alias: Natedaly\DummyjsonClient\Client
```

If you prefer to construct the client through the static factory in Symfony, you can also wire `Client::make()` as a factory service and pass the framework logger into the `$logger` argument.

## Unit Tests

The test suite uses PHPUnit and is focused on unit-level behaviour.

Run all tests:

```bash
composer test
```

Run the unit test suite:

```bash
composer test:unit
```

The current unit tests cover:

- The public `Client` contract and cached user service access.
- The fake client and fake user service used by consuming applications.
- `UserService` behaviour for fetching, listing, and creating users.
- `GuzzleHttpClient` request construction for GET and POST calls.
- Query string handling and JSON payload handling.
- HTTP error mapping, including 404 to `ApiNotFoundException`.
- Transport failure wrapping into `ApiTransportException`.
- Invalid JSON and unexpected payload handling through `InvalidApiResponseException`.
- DTO hydration from arrays and JSON serialization.

The package also ships test fakes:

```php
use Natedaly\DummyjsonClient\Dto\UserDto;
use Natedaly\DummyjsonClient\Testing\FakeDummyJsonClient;
use Natedaly\DummyjsonClient\Testing\FakeUserService;

$users = new FakeUserService();

$users->addUser(new UserDto(
    id: 1,
    firstName: 'Emily',
    lastName: 'Johnson',
    email: 'emily@example.com',
));

$client = new FakeDummyJsonClient($users);
```

These fakes are useful when your application code depends on `DummyJsonClientInterface` and you want fast tests without live HTTP calls.

## Code Quality

The package uses Laravel Pint for code formatting, with the `per` preset and additional rules in `pint.json`. This follows PHP-CS PER 2.0, the newer standard hoisted up from PSR-12.

Check formatting:

```bash
composer format:test
```

Fix formatting:

```bash
composer format
```

Static analysis is handled by PHPStan at level 9 with strict rules enabled.

Run static analysis:

```bash
composer analyse
```

Run the full quality gate:

```bash
composer check
```

`composer check` runs formatting checks, PHPStan analysis, and the PHPUnit test suite.

## How I Used AI

I used AI as an assistant, not as the decision-maker for the package.

AI was used for:

- Generating README content using my own notes, wording, and goals as the driver.
- GitKraken's AI commit message generator to produce more detailed commit summaries.
- Claude Code assistance with some boilerplate, such as sensible Pint and PHPStan rule suggestions.
- Problem solving and rubber ducking while thinking through trade-offs.

I decided the patterns, architecture, tests, and package logic myself. I wanted those choices to reflect how I would personally approach this task from a skill and engineering judgement perspective.

## Addendum

With more time, there are a few areas I would extend:

- Add response caching by leaning on the consuming application or framework's cache service. Rather than forcing a cache implementation into the package, I would keep this injectable so Laravel, Symfony, or another framework could provide its own cache store.
- Add a built-in health check command or cron-friendly job that could call the remote endpoint and alert if DummyJSON became unavailable.
- Explore a richer domain model layer, with a shape that could be extended from or mapped into Laravel Eloquent models, Symfony entities, or another application's own model layer.
- Explore configuration loading through `dotenv`, or provide a fluent configuration API that could be defined at the point the package is registered in Laravel, Symfony, or another consuming framework.
