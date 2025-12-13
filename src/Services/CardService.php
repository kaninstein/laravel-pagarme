<?php

namespace Kaninstein\LaravelPagarme\Services;

use Kaninstein\LaravelPagarme\Client\PagarmeClient;

class CardService
{
    public function __construct(
        protected PagarmeClient $client
    ) {
    }

    /**
     * Create a card for a customer
     *
     * IMPORTANT: If the same card is added twice, the API will return
     * the same card_id from the previously created card.
     *
     * WARNING: Card verification may fail with 412 error:
     * "Could not create credit card. The card verification failed."
     *
     * NOTE: For private label cards, the 'brand' field is required.
     */
    public function create(string $customerId, array $cardData): array
    {
        return $this->client->post("customers/{$customerId}/cards", $cardData);
    }

    /**
     * Get a specific card
     */
    public function get(string $customerId, string $cardId): array
    {
        return $this->client->get("customers/{$customerId}/cards/{$cardId}");
    }

    /**
     * List all cards for a customer (Wallet)
     */
    public function list(string $customerId): array
    {
        return $this->client->get("customers/{$customerId}/cards");
    }

    /**
     * Update card data
     *
     * Only certain fields can be updated:
     * - holder_name
     * - holder_document
     * - exp_month
     * - exp_year
     * - billing_address_id
     */
    public function update(string $customerId, string $cardId, array $cardData): array
    {
        return $this->client->put("customers/{$customerId}/cards/{$cardId}", $cardData);
    }

    /**
     * Delete a card from customer's wallet
     */
    public function delete(string $customerId, string $cardId): array
    {
        return $this->client->delete("customers/{$customerId}/cards/{$cardId}");
    }

    /**
     * Renew a card using Card Updater (manual)
     */
    public function renew(string $customerId, string $cardId): array
    {
        return $this->client->post("customers/{$customerId}/cards/{$cardId}/renew");
    }

    /**
     * Update card expiration date
     */
    public function updateExpiration(
        string $customerId,
        string $cardId,
        int $expMonth,
        int $expYear
    ): array {
        return $this->update($customerId, $cardId, [
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
        ]);
    }

    /**
     * Update card holder name
     */
    public function updateHolderName(
        string $customerId,
        string $cardId,
        string $holderName
    ): array {
        return $this->update($customerId, $cardId, [
            'holder_name' => $holderName,
        ]);
    }

    /**
     * Update card billing address
     */
    public function updateBillingAddress(
        string $customerId,
        string $cardId,
        string $billingAddressId
    ): array {
        return $this->update($customerId, $cardId, [
            'billing_address_id' => $billingAddressId,
        ]);
    }
}
