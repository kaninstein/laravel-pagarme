<?php

namespace Kaninstein\LaravelPagarme\Services;

use Kaninstein\LaravelPagarme\Client\PagarmeClient;

class OrderService
{
    public function __construct(
        protected PagarmeClient $client
    ) {
    }

    /**
     * Create a new order
     */
    public function create(array $data): array
    {
        return $this->client->post('orders', $data);
    }

    /**
     * Get order by ID
     */
    public function get(string $orderId): array
    {
        return $this->client->get("orders/{$orderId}");
    }

    /**
     * List all orders
     */
    public function list(array $params = []): array
    {
        return $this->client->get('orders', $params);
    }

    /**
     * Close order (cancel unfulfilled items)
     */
    public function close(string $orderId): array
    {
        return $this->client->patch("orders/{$orderId}/closed", ['status' => true]);
    }

    /**
     * Get order charges
     */
    public function charges(string $orderId): array
    {
        return $this->client->get("orders/{$orderId}/charges");
    }
}
