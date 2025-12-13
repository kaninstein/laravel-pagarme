<?php

namespace Kaninstein\LaravelPagarme\Services;

use Kaninstein\LaravelPagarme\Client\PagarmeClient;

class WebhookService
{
    public function __construct(
        protected PagarmeClient $client
    ) {
    }

    /**
     * Create a new webhook
     */
    public function create(array $data): array
    {
        return $this->client->post('hooks', $data);
    }

    /**
     * Get webhook by ID
     */
    public function get(string $hookId): array
    {
        return $this->client->get("hooks/{$hookId}");
    }

    /**
     * List all webhooks
     */
    public function list(array $params = []): array
    {
        return $this->client->get('hooks', $params);
    }

    /**
     * Update webhook
     */
    public function update(string $hookId, array $data): array
    {
        return $this->client->put("hooks/{$hookId}", $data);
    }

    /**
     * Delete webhook
     */
    public function delete(string $hookId): array
    {
        return $this->client->delete("hooks/{$hookId}");
    }

    /**
     * Validate webhook signature
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        // Implementar validação de assinatura quando a documentação estiver disponível
        return true;
    }
}
