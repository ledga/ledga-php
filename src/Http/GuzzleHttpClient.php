<?php

declare(strict_types=1);

namespace Ledga\Api\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Ledga\Api\Config;
use Ledga\Api\Exceptions\LedgaAuthenticationException;
use Ledga\Api\Exceptions\LedgaAuthorizationException;
use Ledga\Api\Exceptions\LedgaConflictException;
use Ledga\Api\Exceptions\LedgaException;
use Ledga\Api\Exceptions\LedgaNotFoundException;
use Ledga\Api\Exceptions\LedgaRateLimitException;
use Ledga\Api\Exceptions\LedgaServerException;
use Ledga\Api\Exceptions\LedgaValidationException;

final class GuzzleHttpClient implements HttpClientInterface
{
    private Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client([
            'base_uri' => $this->config->getApiBasePath() . '/',
            'timeout' => $this->config->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function post(string $path, array $data = []): Response
    {
        return $this->request('POST', $path, ['json' => $data]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function put(string $path, array $data = []): Response
    {
        return $this->request('PUT', $path, ['json' => $data]);
    }

    public function delete(string $path): Response
    {
        return $this->request('DELETE', $path);
    }

    /**
     * @param array<string, mixed> $options
     * @throws LedgaException
     */
    private function request(string $method, string $path, array $options = []): Response
    {
        try {
            $response = $this->client->request($method, ltrim($path, '/'), $options);

            $body = $response->getBody()->getContents();
            /** @var array<string, mixed> $data */
            $data = $body !== '' ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : [];

            return new Response(
                $response->getStatusCode(),
                $data,
                $response->getHeaders(),
            );
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (ServerException $e) {
            $this->handleServerException($e);
        } catch (GuzzleException $e) {
            throw new LedgaException('HTTP request failed: ' . $e->getMessage(), null, $e);
        } catch (\JsonException $e) {
            throw new LedgaException('Failed to decode response: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * @throws LedgaException
     * @return never
     */
    private function handleClientException(ClientException $e): void
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        /** @var array<string, mixed>|null $errorBody */
        $errorBody = null;
        try {
            $errorBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Ignore JSON decode errors
        }

        $message = $this->extractErrorMessage($errorBody, $body);

        match ($statusCode) {
            401 => throw new LedgaAuthenticationException($message, $errorBody, $e),
            403 => throw new LedgaAuthorizationException($message, $errorBody, $e),
            404 => throw new LedgaNotFoundException($message, $errorBody, $e),
            409 => throw new LedgaConflictException($message, $errorBody, $e),
            422, 400 => throw new LedgaValidationException($message, $errorBody, $e),
            429 => throw new LedgaRateLimitException(
                $message,
                $errorBody,
                $this->extractRetryAfter($response->getHeaders()),
                $e,
            ),
            default => throw new LedgaException($message, $errorBody, $e),
        };
    }

    /**
     * @throws LedgaServerException
     * @return never
     */
    private function handleServerException(ServerException $e): void
    {
        $response = $e->getResponse();
        $body = $response->getBody()->getContents();

        /** @var array<string, mixed>|null $errorBody */
        $errorBody = null;
        try {
            $errorBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Ignore JSON decode errors
        }

        $message = $this->extractErrorMessage($errorBody, $body);
        throw new LedgaServerException($message, $errorBody, $e);
    }

    /**
     * @param array<string, mixed>|null $errorBody
     */
    private function extractErrorMessage(?array $errorBody, string $fallback): string
    {
        if ($errorBody === null) {
            return $fallback ?: 'Unknown error';
        }

        return $errorBody['message']
            ?? $errorBody['error']
            ?? $fallback
            ?: 'Unknown error';
    }

    /**
     * @param array<string, string[]> $headers
     */
    private function extractRetryAfter(array $headers): ?int
    {
        foreach ($headers as $name => $values) {
            if (strtolower($name) === 'retry-after' && isset($values[0])) {
                return (int) $values[0];
            }
        }
        return null;
    }
}
