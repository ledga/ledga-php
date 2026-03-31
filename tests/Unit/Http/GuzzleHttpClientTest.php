<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Ledga\Api\Config;
use Ledga\Api\Exceptions\LedgaAuthenticationException;
use Ledga\Api\Exceptions\LedgaAuthorizationException;
use Ledga\Api\Exceptions\LedgaConflictException;
use Ledga\Api\Exceptions\LedgaNotFoundException;
use Ledga\Api\Exceptions\LedgaRateLimitException;
use Ledga\Api\Exceptions\LedgaServerException;
use Ledga\Api\Exceptions\LedgaValidationException;
use Ledga\Api\Http\GuzzleHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GuzzleHttpClientTest extends TestCase
{
    #[Test]
    public function it_makes_successful_get_request(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => ['id' => '123']])),
        ]);

        $client = $this->createClient($mock);
        $response = $client->get('accounts');

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(['data' => ['id' => '123']], $response->data);
    }

    #[Test]
    public function it_makes_successful_post_request(): void
    {
        $mock = new MockHandler([
            new Response(201, [], json_encode(['id' => '123', 'name' => 'Test'])),
        ]);

        $client = $this->createClient($mock);
        $response = $client->post('accounts', ['name' => 'Test']);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('123', $response->data['id']);
    }

    #[Test]
    public function it_throws_authentication_exception_on_401(): void
    {
        $mock = new MockHandler([
            new Response(401, [], json_encode(['message' => 'Invalid API key'])),
        ]);

        $client = $this->createClient($mock);

        $this->expectException(LedgaAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');

        $client->get('accounts');
    }

    #[Test]
    public function it_throws_authorization_exception_on_403(): void
    {
        $mock = new MockHandler([
            new Response(403, [], json_encode(['message' => 'Forbidden'])),
        ]);

        $client = $this->createClient($mock);

        $this->expectException(LedgaAuthorizationException::class);
        $client->get('accounts');
    }

    #[Test]
    public function it_throws_not_found_exception_on_404(): void
    {
        $mock = new MockHandler([
            new Response(404, [], json_encode(['message' => 'Account not found'])),
        ]);

        $client = $this->createClient($mock);

        $this->expectException(LedgaNotFoundException::class);
        $client->get('accounts/123');
    }

    #[Test]
    public function it_throws_conflict_exception_on_409(): void
    {
        $mock = new MockHandler([
            new Response(409, [], json_encode(['message' => 'Idempotency key conflict'])),
        ]);

        $client = $this->createClient($mock);

        $this->expectException(LedgaConflictException::class);
        $client->post('transactions', []);
    }

    #[Test]
    #[DataProvider('validationStatusCodes')]
    public function it_throws_validation_exception_on_validation_errors(int $statusCode): void
    {
        $mock = new MockHandler([
            new Response($statusCode, [], json_encode([
                'message' => 'Validation failed',
                'errors' => ['name' => ['Name is required']],
            ])),
        ]);

        $client = $this->createClient($mock);

        try {
            $client->post('accounts', []);
            $this->fail('Expected LedgaValidationException');
        } catch (LedgaValidationException $e) {
            $this->assertSame(['name' => ['Name is required']], $e->getErrors());
        }
    }

    /**
     * @return array<string, array{int}>
     */
    public static function validationStatusCodes(): array
    {
        return [
            '400' => [400],
            '422' => [422],
        ];
    }

    #[Test]
    public function it_throws_rate_limit_exception_with_retry_after(): void
    {
        $mock = new MockHandler([
            new Response(429, ['Retry-After' => '60'], json_encode(['message' => 'Too many requests'])),
        ]);

        $client = $this->createClient($mock);

        try {
            $client->get('accounts');
            $this->fail('Expected LedgaRateLimitException');
        } catch (LedgaRateLimitException $e) {
            $this->assertSame(60, $e->getRetryAfter());
        }
    }

    #[Test]
    public function it_throws_server_exception_on_5xx(): void
    {
        $mock = new MockHandler([
            new Response(500, [], json_encode(['message' => 'Internal server error'])),
        ]);

        $client = $this->createClient($mock);

        $this->expectException(LedgaServerException::class);
        $client->get('accounts');
    }

    private function createClient(MockHandler $mock): GuzzleHttpClient
    {
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);
        $config = new Config('test-api-key', 'https://test.ledga.io');

        return new GuzzleHttpClient($config, $guzzle);
    }
}
