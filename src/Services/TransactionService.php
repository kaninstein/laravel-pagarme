<?php

namespace Kaninstein\LaravelPagarme\Services;

use Kaninstein\LaravelPagarme\Client\PagarmeClient;

class TransactionService
{
    public function __construct(
        protected PagarmeClient $client
    ) {
    }

    /**
     * Get PIX QR Code payload for a transaction.
     *
     * @return array<string, mixed>
     */
    public function qrcode(string $transactionId, string $paymentMethod = 'pix'): array
    {
        return $this->client->get("transactions/{$transactionId}/qrcode", [
            'payment_method' => $paymentMethod,
        ]);
    }
}
