<?php

namespace Kaninstein\LaravelPagarme\Services;

use Kaninstein\LaravelPagarme\Client\PagarmeClient;

class CustomerService
{
    public function __construct(
        protected PagarmeClient $client
    ) {
    }

    /**
     * Create a new customer
     *
     * IMPORTANT: Email is unique. If a customer with the same email exists,
     * it will UPDATE the existing customer instead of creating a new one.
     */
    public function create(array $data): array
    {
        return $this->client->post('customers', $data);
    }

    /**
     * Get customer by ID
     */
    public function get(string $customerId): array
    {
        return $this->client->get("customers/{$customerId}");
    }

    /**
     * Update customer
     *
     * WARNING: This endpoint replaces ALL customer data.
     * Any field not sent will be set to null.
     * Make sure to include all customer data when updating.
     */
    public function update(string $customerId, array $data): array
    {
        return $this->client->put("customers/{$customerId}", $data);
    }

    /**
     * List all customers with optional filters
     *
     * @param array $params Available filters:
     *   - name: string - Customer name
     *   - document: string - CPF or CNPJ
     *   - email: string - Customer email
     *   - gender: string - 'male' or 'female'
     *   - code: string - Customer code in merchant system
     *   - page: int - Page number (default: 1)
     *   - size: int - Items per page (default: 10)
     */
    public function list(array $params = []): array
    {
        return $this->client->get('customers', $params);
    }

    /**
     * Search customers by name
     */
    public function searchByName(string $name, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'name' => $name,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Search customers by email
     */
    public function searchByEmail(string $email, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'email' => $email,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Search customers by document (CPF/CNPJ)
     */
    public function searchByDocument(string $document, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'document' => $document,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Search customers by code
     */
    public function searchByCode(string $code, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'code' => $code,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Filter customers by gender
     */
    public function filterByGender(string $gender, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'gender' => $gender,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Delete customer
     */
    public function delete(string $customerId): array
    {
        return $this->client->delete("customers/{$customerId}");
    }

    /**
     * Get customer cards
     */
    public function cards(string $customerId): array
    {
        return $this->client->get("customers/{$customerId}/cards");
    }

    /**
     * Create card for customer
     */
    public function createCard(string $customerId, array $cardData): array
    {
        return $this->client->post("customers/{$customerId}/cards", $cardData);
    }

    /**
     * Delete customer card
     */
    public function deleteCard(string $customerId, string $cardId): array
    {
        return $this->client->delete("customers/{$customerId}/cards/{$cardId}");
    }

    /**
     * Get customer addresses
     */
    public function addresses(string $customerId): array
    {
        return $this->client->get("customers/{$customerId}/addresses");
    }

    /**
     * Create address for customer
     */
    public function createAddress(string $customerId, array $addressData): array
    {
        return $this->client->post("customers/{$customerId}/addresses", $addressData);
    }

    /**
     * Update customer address
     */
    public function updateAddress(string $customerId, string $addressId, array $addressData): array
    {
        return $this->client->put("customers/{$customerId}/addresses/{$addressId}", $addressData);
    }

    /**
     * Delete customer address
     */
    public function deleteAddress(string $customerId, string $addressId): array
    {
        return $this->client->delete("customers/{$customerId}/addresses/{$addressId}");
    }
}
