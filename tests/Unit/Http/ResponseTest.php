<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Http;

use Ledga\Api\Exceptions\LedgaException;
use Ledga\Api\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    #[Test]
    public function unwrap_returns_inner_data_for_object_envelope(): void
    {
        $response = new Response(200, ['success' => true, 'data' => ['id' => 'abc', 'name' => 'thing']]);

        $this->assertSame(['id' => 'abc', 'name' => 'thing'], $response->unwrap());
    }

    #[Test]
    public function unwrap_returns_full_body_for_list_shaped_data(): void
    {
        $body = ['success' => true, 'data' => [['id' => '1'], ['id' => '2']], 'meta' => ['pagination' => []]];
        $response = new Response(200, $body);

        $this->assertSame($body, $response->unwrap());
    }

    #[Test]
    public function unwrap_returns_full_body_when_data_is_an_empty_list(): void
    {
        $body = ['success' => true, 'data' => [], 'meta' => ['pagination' => []]];
        $response = new Response(200, $body);

        $this->assertSame($body, $response->unwrap());
    }

    #[Test]
    public function unwrap_returns_full_body_when_data_key_missing(): void
    {
        $body = ['id' => 'abc', 'name' => 'thing'];
        $response = new Response(200, $body);

        $this->assertSame($body, $response->unwrap());
    }

    #[Test]
    public function unwrap_throws_when_data_is_null(): void
    {
        $response = new Response(200, ['success' => true, 'data' => null]);

        $this->expectException(LedgaException::class);
        $this->expectExceptionMessage('Malformed response');

        $response->unwrap();
    }

    #[Test]
    public function unwrap_throws_when_data_is_a_scalar(): void
    {
        $response = new Response(200, ['success' => true, 'data' => 'not-an-array']);

        $this->expectException(LedgaException::class);

        $response->unwrap();
    }
}
