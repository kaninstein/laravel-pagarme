<?php

namespace Kaninstein\LaravelPagarme\Services;

use Illuminate\Support\Facades\Http;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;

class TokenService
{
    protected string $publicKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->publicKey = config('pagarme.public_key');
        $this->apiUrl = config('pagarme.api_url', 'https://api.pagar.me/core/v5');
    }

    /**
     * Create a card token
     *
     * IMPORTANT SECURITY NOTES:
     * 1. This endpoint uses PUBLIC_KEY (not secret key!)
     * 2. Only Content-Type header is allowed (no Authorization header)
     * 3. Public key is sent as 'appId' query parameter
     * 4. Make sure your domain is registered in Pagarme dashboard
     * 5. Billing address is NOT tokenized - must be sent separately when creating order
     *
     * @param array $cardData Card data to tokenize
     * @return array Token data including 'id' field
     */
    public function createCardToken(array $cardData): array
    {
        if (empty($this->publicKey)) {
            throw new PagarmeException('Public key is not configured');
        }

        $url = rtrim($this->apiUrl, '/') . '/tokens?appId=' . $this->publicKey;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->timeout(config('pagarme.timeout', 30))
        ->post($url, [
            'type' => 'card',
            'card' => $cardData,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw PagarmeException::fromResponse($response);
    }

    /**
     * Create token from CreditCardDTO
     */
    public function tokenizeCard(array $cardData): string
    {
        $result = $this->createCardToken($cardData);
        return $result['id'] ?? throw new PagarmeException('Token ID not found in response');
    }

    /**
     * Helper to create full card token data structure
     */
    public static function prepareCardData(
        string $number,
        string $holderName,
        int $expMonth,
        int $expYear,
        string $cvv,
        ?string $holderDocument = null,
    ): array {
        return array_filter([
            'number' => $number,
            'holder_name' => $holderName,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'cvv' => $cvv,
            'holder_document' => $holderDocument,
        ], fn ($value) => $value !== null);
    }
}
