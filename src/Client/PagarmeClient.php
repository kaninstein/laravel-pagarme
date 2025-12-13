<?php

namespace Kaninstein\LaravelPagarme\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kaninstein\LaravelPagarme\Exceptions\AuthenticationException;
use Kaninstein\LaravelPagarme\Exceptions\BadRequestException;
use Kaninstein\LaravelPagarme\Exceptions\ForbiddenException;
use Kaninstein\LaravelPagarme\Exceptions\NotFoundException;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;
use Kaninstein\LaravelPagarme\Exceptions\PreconditionFailedException;
use Kaninstein\LaravelPagarme\Exceptions\TooManyRequestsException;
use Kaninstein\LaravelPagarme\Exceptions\ValidationException;

class PagarmeClient
{
    protected string $secretKey;
    protected string $apiUrl;
    protected int $timeout;

    public function __construct(
        string $secretKey,
        string $apiUrl = 'https://api.pagar.me/core/v5',
        int $timeout = 30
    ) {
        $this->secretKey = $secretKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Create HTTP client with Basic Auth
     */
    protected function createHttpClient(): PendingRequest
    {
        // Basic Auth: user = secretKey, password = empty
        $credentials = base64_encode($this->secretKey . ':');

        return Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->retry(
            config('pagarme.retry.times', 3),
            config('pagarme.retry.sleep', 1000),
            fn ($exception) => $this->shouldRetry($exception)
        );
    }

    /**
     * Determine if request should be retried
     */
    protected function shouldRetry(\Exception $exception): bool
    {
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            $statusCode = $exception->response->status();
            // Retry on 5xx errors and 429 (rate limit)
            return $statusCode >= 500 || $statusCode === 429;
        }

        return false;
    }

    /**
     * Make GET request
     */
    public function get(string $endpoint, array $query = []): array
    {
        $url = $this->buildUrl($endpoint);

        $this->logRequest('GET', $url, ['query' => $query]);

        $response = $this->createHttpClient()->get($url, $query);

        return $this->handleResponse($response);
    }

    /**
     * Make POST request
     */
    public function post(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);

        $this->logRequest('POST', $url, $data);

        $response = $this->createHttpClient()->post($url, $data);

        return $this->handleResponse($response);
    }

    /**
     * Make PUT request
     */
    public function put(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);

        $this->logRequest('PUT', $url, $data);

        $response = $this->createHttpClient()->put($url, $data);

        return $this->handleResponse($response);
    }

    /**
     * Make PATCH request
     */
    public function patch(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);

        $this->logRequest('PATCH', $url, $data);

        $response = $this->createHttpClient()->patch($url, $data);

        return $this->handleResponse($response);
    }

    /**
     * Make DELETE request
     */
    public function delete(string $endpoint): array
    {
        $url = $this->buildUrl($endpoint);

        $this->logRequest('DELETE', $url);

        $response = $this->createHttpClient()->delete($url);

        return $this->handleResponse($response);
    }

    /**
     * Build full URL
     */
    protected function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');
        return "{$this->apiUrl}/{$endpoint}";
    }

    /**
     * Handle HTTP response
     */
    protected function handleResponse(Response $response): array
    {
        $this->logResponse($response);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->throwException($response);
    }

    /**
     * Throw appropriate exception based on status code
     */
    protected function throwException(Response $response): never
    {
        $statusCode = $response->status();

        throw match ($statusCode) {
            400 => BadRequestException::fromResponse($response),
            401 => AuthenticationException::fromResponse($response),
            403 => ForbiddenException::fromResponse($response),
            404 => NotFoundException::fromResponse($response),
            412 => PreconditionFailedException::fromResponse($response),
            422 => ValidationException::fromResponse($response),
            429 => TooManyRequestsException::fromResponse($response),
            default => PagarmeException::fromResponse($response),
        };
    }

    /**
     * Log request if logging is enabled
     */
    protected function logRequest(string $method, string $url, array $data = []): void
    {
        if (!config('pagarme.logging.enabled')) {
            return;
        }

        Log::channel(config('pagarme.logging.channel'))
            ->info('Pagarme API Request', [
                'method' => $method,
                'url' => $url,
                'data' => $data,
            ]);
    }

    /**
     * Log response if logging is enabled
     */
    protected function logResponse(Response $response): void
    {
        if (!config('pagarme.logging.enabled')) {
            return;
        }

        Log::channel(config('pagarme.logging.channel'))
            ->info('Pagarme API Response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
    }

    /**
     * Get API URL
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Get secret key (masked for security)
     */
    public function getSecretKey(): string
    {
        return substr($this->secretKey, 0, 8) . '...' . substr($this->secretKey, -4);
    }
}
