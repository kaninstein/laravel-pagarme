<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Authentication Data Transfer Object
 *
 * Used for authenticated credit and debit card transactions
 * Currently only supports 3D Secure authentication
 */
class AuthenticationDTO
{
    public function __construct(
        public string $type,
        public ThreeDSecureDTO $threeDSecure,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            threeDSecure: ThreeDSecureDTO::fromArray($data['threed_secure']),
        );
    }

    /**
     * Create with 3D Secure authentication
     */
    public static function threeDSecure(ThreeDSecureDTO $threeDSecure): self
    {
        return new self(
            type: 'threed_secure',
            threeDSecure: $threeDSecure,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'threed_secure' => $this->threeDSecure->toArray(),
        ];
    }

    /**
     * Validate authentication data
     */
    public function validate(): array
    {
        $errors = [];

        // Type validation
        if ($this->type !== 'threed_secure') {
            $errors[] = 'Authentication type must be "threed_secure"';
        }

        // Validate 3DS data
        $threeDSecureErrors = $this->threeDSecure->validate();
        if (!empty($threeDSecureErrors)) {
            $errors = array_merge($errors, $threeDSecureErrors);
        }

        return $errors;
    }

    /**
     * Check if authentication data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
