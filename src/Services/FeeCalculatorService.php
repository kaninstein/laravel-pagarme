<?php

namespace Kaninstein\LaravelPagarme\Services;

use Illuminate\Support\Facades\Cache;
use Kaninstein\LaravelPagarme\Client\PagarmeClient;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;
use Illuminate\Support\Carbon;

class FeeCalculatorService
{
    private const DEFAULT_CAPTURE_METHOD = 'ecommerce';
    private const DEFAULT_ENDPOINT = 'transactions/fee-calculator';

    private const ALLOWED_FEE_RESPONSIBILITY = ['buyer', 'merchant'];
    private const ALLOWED_CARD_BRANDS = [
        'amex',
        'aura',
        'diners',
        'discover',
        'elo',
        'hipercard',
        'jcb',
        'mastercard',
        'visa',
    ];
    private const ALLOWED_CAPTURE_METHODS = [
        'ecommerce',
        'emv',
        'magstripe',
        'emv_contactless',
        'magstripe_contactless',
    ];

    public function __construct(
        private readonly PagarmeClient $client
    ) {}

    /**
     * Calcula taxas e parcelamento via endpoint fee-calculator.
     *
     * @param array $payload Esperado:
     *  - amount (int, centavos, >= 1)
     *  - fee_responsibility ("buyer"|"merchant")
     *  - credit_card.installments (1..12)
     *  - credit_card.card_brand (lista suportada)
     *  - credit_card.capture_method (opcional; default: ecommerce)
     * @param bool $useCache Cache por 1 mês (default: true)
     */
    public function calculate(array $payload, bool $useCache = true): array
    {
        return $this->calculateWithApiKey(apiKey: null, payload: $payload, useCache: $useCache);
    }

    /**
     * Mesmo cálculo, mas permitindo sobrescrever a chave usada no Basic Auth.
     */
    public function calculateWithApiKey(?string $apiKey, array $payload, bool $useCache = true): array
    {
        $normalizedPayload = $this->normalizePayload($payload);
        $this->validatePayload($normalizedPayload);

        $endpoint = config('pagarme.fee_calculator.endpoint', self::DEFAULT_ENDPOINT);

        $cacheExpiration = $this->resolveCacheExpiration(config('pagarme.fee_calculator.cache_ttl', 'month'));
        $shouldCache = $useCache && $cacheExpiration !== null;

        $client = $this->clientFor($apiKey);

        if (!$shouldCache) {
            return $client->get($endpoint, $normalizedPayload);
        }

        $cacheKey = $this->cacheKey($apiKey, $endpoint, $normalizedPayload);

        return Cache::remember(
            $cacheKey,
            $cacheExpiration,
            fn () => $client->get($endpoint, $normalizedPayload)
        );
    }

    public function clearCache(?string $apiKey = null, array $payload = []): void
    {
        $endpoint = config('pagarme.fee_calculator.endpoint', self::DEFAULT_ENDPOINT);

        if ($payload === []) {
            return;
        }

        $normalizedPayload = $this->normalizePayload($payload);
        Cache::forget($this->cacheKey($apiKey, $endpoint, $normalizedPayload));
    }

    private function clientFor(?string $apiKey): PagarmeClient
    {
        if (!$apiKey) {
            return $this->client;
        }

        return new PagarmeClient(
            secretKey: $apiKey,
            apiUrl: config('pagarme.api_url'),
            timeout: (int) config('pagarme.timeout', 30)
        );
    }

    private function normalizePayload(array $payload): array
    {
        if (!isset($payload['credit_card']) || !is_array($payload['credit_card'])) {
            return $payload;
        }

        if (!isset($payload['credit_card']['capture_method']) || $payload['credit_card']['capture_method'] === null) {
            $payload['credit_card']['capture_method'] = self::DEFAULT_CAPTURE_METHOD;
        }

        if (isset($payload['credit_card']['card_brand']) && is_string($payload['credit_card']['card_brand'])) {
            $payload['credit_card']['card_brand'] = strtolower($payload['credit_card']['card_brand']);
        }

        if (isset($payload['fee_responsibility']) && is_string($payload['fee_responsibility'])) {
            $payload['fee_responsibility'] = strtolower($payload['fee_responsibility']);
        }

        return $payload;
    }

    private function validatePayload(array $payload): void
    {
        $amount = $payload['amount'] ?? null;
        if (!is_int($amount) || $amount < 1) {
            throw new PagarmeException('amount must be an integer in cents and >= 1');
        }

        $feeResponsibility = $payload['fee_responsibility'] ?? null;
        if (!is_string($feeResponsibility) || !in_array($feeResponsibility, self::ALLOWED_FEE_RESPONSIBILITY, true)) {
            throw new PagarmeException('fee_responsibility must be "buyer" or "merchant"');
        }

        $creditCard = $payload['credit_card'] ?? null;
        if (!is_array($creditCard)) {
            throw new PagarmeException('credit_card must be an object');
        }

        $installments = $creditCard['installments'] ?? null;
        if (!is_int($installments) || $installments < 1 || $installments > 12) {
            throw new PagarmeException('credit_card.installments must be an integer between 1 and 12');
        }

        $brand = $creditCard['card_brand'] ?? null;
        if (!is_string($brand) || !in_array(strtolower($brand), self::ALLOWED_CARD_BRANDS, true)) {
            throw new PagarmeException('credit_card.card_brand is invalid');
        }

        $captureMethod = $creditCard['capture_method'] ?? self::DEFAULT_CAPTURE_METHOD;
        if (!is_string($captureMethod) || !in_array($captureMethod, self::ALLOWED_CAPTURE_METHODS, true)) {
            throw new PagarmeException('credit_card.capture_method is invalid');
        }
    }

    private function cacheKey(?string $apiKey, string $endpoint, array $payload): string
    {
        $prefix = (string) config('pagarme.fee_calculator.cache_prefix', 'pagarme:fee_calculator');
        $keyMarker = $apiKey ? substr(hash('sha256', $apiKey), 0, 16) : 'default_key';
        $payloadHash = hash('sha256', json_encode($this->sortRecursive($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return "{$prefix}:{$keyMarker}:{$endpoint}:{$payloadHash}";
    }

    /**
     * @return \DateTimeInterface|int|null
     */
    private function resolveCacheExpiration(mixed $ttl): mixed
    {
        if ($ttl === null || $ttl === false) {
            return null;
        }

        if (is_string($ttl)) {
            $normalized = strtolower(trim($ttl));
            if ($normalized === 'month' || $normalized === '1_month' || $normalized === 'one_month') {
                return now()->addMonth();
            }

            if (is_numeric($normalized)) {
                $ttl = (int) $normalized;
            }
        }

        if (is_int($ttl)) {
            return $ttl > 0 ? now()->addSeconds($ttl) : null;
        }

        if ($ttl instanceof \DateTimeInterface) {
            return $ttl;
        }

        if ($ttl instanceof Carbon) {
            return $ttl;
        }

        return null;
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        ksort($value);
        return $value;
    }
}
