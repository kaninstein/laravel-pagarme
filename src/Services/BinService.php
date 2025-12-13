<?php

namespace Kaninstein\LaravelPagarme\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Kaninstein\LaravelPagarme\DTOs\BinDTO;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;
use Kaninstein\LaravelPagarme\Exceptions\NotFoundException;

class BinService
{
    protected string $secretKey;
    protected string $binUrl;
    protected int $timeout;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->secretKey = config('pagarme.secret_key');
        $this->binUrl = 'https://api.pagar.me/bin/v1';
        $this->timeout = config('pagarme.timeout', 30);
        $this->cacheTtl = config('pagarme.bin_cache_ttl', 3600); // 1 hour default
    }

    /**
     * Get BIN information
     *
     * The BIN (Bank Identifier Number) are the first 6 digits of a card number
     * that identify the institution that issued the card.
     *
     * @param string $bin First 6 digits of card number
     * @param bool $useCache Whether to use cache (default: true)
     * @return BinDTO
     */
    public function get(string $bin, bool $useCache = true): BinDTO
    {
        // Validate BIN (must be 6 digits)
        if (!preg_match('/^\d{6}$/', $bin)) {
            throw new PagarmeException('BIN must be exactly 6 digits');
        }

        // Check cache first
        if ($useCache) {
            $cached = $this->getFromCache($bin);
            if ($cached) {
                return $cached;
            }
        }

        // Make API request
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
            'Accept' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->get("{$this->binUrl}/{$bin}");

        if ($response->successful()) {
            $data = $response->json();
            $binDTO = BinDTO::fromArray($data);

            // Cache the result
            if ($useCache) {
                $this->saveToCache($bin, $binDTO);
            }

            return $binDTO;
        }

        if ($response->status() === 404) {
            throw new NotFoundException("BIN not found: {$bin}");
        }

        throw PagarmeException::fromResponse($response);
    }

    /**
     * Get BIN info from card number
     * Extracts first 6 digits automatically
     */
    public function getFromCardNumber(string $cardNumber, bool $useCache = true): BinDTO
    {
        // Remove non-numeric characters
        $cleaned = preg_replace('/\D/', '', $cardNumber);

        // Extract first 6 digits
        $bin = substr($cleaned, 0, 6);

        return $this->get($bin, $useCache);
    }

    /**
     * Check if BIN exists
     */
    public function exists(string $bin): bool
    {
        try {
            $this->get($bin);
            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * Get brand from card number
     */
    public function getBrand(string $cardNumber): string
    {
        $binInfo = $this->getFromCardNumber($cardNumber);
        return $binInfo->brand;
    }

    /**
     * Get CVV length from card number
     */
    public function getCvvLength(string $cardNumber): int
    {
        $binInfo = $this->getFromCardNumber($cardNumber);
        return $binInfo->cvv;
    }

    /**
     * Validate card number length against BIN info
     */
    public function isValidCardLength(string $cardNumber): bool
    {
        $cleaned = preg_replace('/\D/', '', $cardNumber);
        $binInfo = $this->getFromCardNumber($cardNumber);

        return in_array(strlen($cleaned), $binInfo->lengths);
    }

    /**
     * Format card number with proper spacing
     */
    public function formatCardNumber(string $cardNumber): string
    {
        $binInfo = $this->getFromCardNumber($cardNumber);
        $cleaned = preg_replace('/\D/', '', $cardNumber);

        return $binInfo->formatCardNumber($cleaned);
    }

    /**
     * Clear BIN cache
     */
    public function clearCache(?string $bin = null): void
    {
        if ($bin) {
            Cache::forget($this->getCacheKey($bin));
        } else {
            // Clear all BIN cache
            // This would require tracking all cached BINs
            // For now, just note that cache will expire naturally
        }
    }

    /**
     * Get from cache
     */
    protected function getFromCache(string $bin): ?BinDTO
    {
        $cached = Cache::get($this->getCacheKey($bin));

        if ($cached && is_array($cached)) {
            return BinDTO::fromArray($cached);
        }

        return null;
    }

    /**
     * Save to cache
     */
    protected function saveToCache(string $bin, BinDTO $binDTO): void
    {
        Cache::put(
            $this->getCacheKey($bin),
            $binDTO->toArray(),
            $this->cacheTtl
        );
    }

    /**
     * Get cache key
     */
    protected function getCacheKey(string $bin): string
    {
        return "pagarme:bin:{$bin}";
    }
}
