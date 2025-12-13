<?php

namespace Kaninstein\LaravelPagarme\DTOs;

use Kaninstein\LaravelPagarme\DTOs\GooglePay\GooglePayDTO;

/**
 * Payload Data Transfer Object
 *
 * For encrypted payment data like Google Pay
 */
class PayloadDTO
{
    public function __construct(
        public string $type,
        public ?GooglePayDTO $googlePay = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $googlePay = null;
        if (isset($data['google_pay']) && is_array($data['google_pay'])) {
            $googlePay = GooglePayDTO::fromArray($data['google_pay']);
        }

        return new self(
            type: $data['type'],
            googlePay: $googlePay,
        );
    }

    /**
     * Create for Google Pay
     */
    public static function googlePay(GooglePayDTO $googlePay): self
    {
        return new self(
            type: 'google_pay',
            googlePay: $googlePay,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
        ];

        if ($this->googlePay !== null) {
            $data['google_pay'] = $this->googlePay->toArray();
        }

        return $data;
    }

    /**
     * Validate payload data
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->type !== 'google_pay') {
            $errors[] = 'Currently only google_pay type is supported';
        }

        if ($this->type === 'google_pay') {
            if ($this->googlePay === null) {
                $errors[] = 'Google Pay data is required when type is google_pay';
            } else {
                $googlePayErrors = $this->googlePay->validate();
                $errors = array_merge($errors, $googlePayErrors);
            }
        }

        return $errors;
    }

    /**
     * Check if payload data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
