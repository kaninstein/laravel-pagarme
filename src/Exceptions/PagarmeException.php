<?php

namespace Kaninstein\LaravelPagarme\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class PagarmeException extends Exception
{
    protected ?Response $response = null;
    protected array $errors = [];
    protected ?string $requestId = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Response $response = null
    ) {
        parent::__construct($message, $code);

        $this->response = $response;

        if ($response) {
            $this->parseErrors();
        }
    }

    /**
     * Create exception from HTTP response
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $message = $body['message'] ?? 'An error occurred with Pagarme API';

        return new self($message, $response->status(), $response);
    }

    /**
     * Parse errors from response
     */
    protected function parseErrors(): void
    {
        if (!$this->response) {
            return;
        }

        $body = $this->response->json();

        if (isset($body['errors']) && is_array($body['errors'])) {
            $this->errors = $body['errors'];
        }

        $this->requestId = $this->response->header('x-request-id')
            ?? $this->response->header('X-Request-Id')
            ?? $this->response->header('request-id')
            ?? $this->response->header('Request-Id');
    }

    /**
     * Get the HTTP response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Get all errors from response
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Check if has specific error
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get error for specific field
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
