<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * SafetyPay Payment Data Transfer Object
 *
 * For SafetyPay payments
 * Only available for Gateway clients
 *
 * SafetyPay does not have specific parameters,
 * only requires payment_method to be set to "safetypay"
 */
class SafetyPayPaymentDTO
{
    public function __construct(
        public ?array $metadata = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create SafetyPay payment
     */
    public static function create(?array $metadata = null): self
    {
        return new self(metadata: $metadata);
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
