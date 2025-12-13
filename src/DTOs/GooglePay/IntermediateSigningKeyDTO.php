<?php

namespace Kaninstein\LaravelPagarme\DTOs\GooglePay;

/**
 * Intermediate Signing Key Data Transfer Object
 *
 * Part of Google Pay token structure
 */
class IntermediateSigningKeyDTO
{
    public function __construct(
        public string $signedKey,
        public array $signatures,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            signedKey: $data['signed_key'],
            signatures: $data['signatures'],
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        return [
            'signed_key' => $this->signedKey,
            'signatures' => $this->signatures,
        ];
    }
}
