<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Network Token Data Transfer Object
 *
 * For tokenized cards from card network services (Mastercard, Visa)
 * Pass Through functionality - Gateway clients only
 *
 * IMPORTANT: A new cryptogram must be generated for each transaction
 */
class NetworkTokenDTO
{
    public function __construct(
        public string $number,
        public string $holderName,
        public int $expMonth,
        public int $expYear,
        public string $cryptogram,
        public ?AddressDTO $billingAddress = null,
        public ?string $eci = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $billingAddress = null;
        if (isset($data['billing_address']) && is_array($data['billing_address'])) {
            $billingAddress = AddressDTO::fromArray($data['billing_address']);
        }

        return new self(
            number: $data['number'],
            holderName: $data['holder_name'],
            expMonth: $data['exp_month'],
            expYear: $data['exp_year'],
            cryptogram: $data['cryptogram'],
            billingAddress: $billingAddress,
            eci: $data['eci'] ?? null,
        );
    }

    /**
     * Create network token
     */
    public static function create(
        string $number,
        string $holderName,
        int $expMonth,
        int $expYear,
        string $cryptogram,
        ?AddressDTO $billingAddress = null,
        ?string $eci = null
    ): self {
        return new self(
            number: $number,
            holderName: $holderName,
            expMonth: $expMonth,
            expYear: $expYear,
            cryptogram: $cryptogram,
            billingAddress: $billingAddress,
            eci: $eci,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'number' => $this->number,
            'holder_name' => $this->holderName,
            'exp_month' => $this->expMonth,
            'exp_year' => $this->expYear,
            'cryptogram' => $this->cryptogram,
        ];

        if ($this->billingAddress !== null) {
            $data['billing_address'] = $this->billingAddress instanceof AddressDTO
                ? $this->billingAddress->toArray()
                : $this->billingAddress;
        }

        if ($this->eci !== null) {
            $data['eci'] = $this->eci;
        }

        return $data;
    }

    /**
     * Validate network token data
     */
    public function validate(): array
    {
        $errors = [];

        // Number validation (13-19 digits)
        $cleanNumber = preg_replace('/\D/', '', $this->number);
        if (strlen($cleanNumber) < 13 || strlen($cleanNumber) > 19) {
            $errors[] = 'Token number must be between 13 and 19 digits';
        }

        // Holder name validation
        if (strlen($this->holderName) > 64) {
            $errors[] = 'Holder name must not exceed 64 characters';
        }

        if (empty(trim($this->holderName))) {
            $errors[] = 'Holder name is required';
        }

        // Expiration month validation
        if ($this->expMonth < 1 || $this->expMonth > 12) {
            $errors[] = 'Expiration month must be between 1 and 12';
        }

        // Expiration year validation (2-digit or 4-digit)
        if ($this->expYear < 0) {
            $errors[] = 'Expiration year must be positive';
        }

        // Cryptogram validation
        if (empty($this->cryptogram)) {
            $errors[] = 'Cryptogram is required for network tokens';
        }

        // Validate billing address if provided
        if ($this->billingAddress instanceof AddressDTO) {
            $addressErrors = $this->billingAddress->validate();
            $errors = array_merge($errors, $addressErrors);
        }

        // ECI validation (2 characters max)
        if ($this->eci !== null && strlen($this->eci) > 2) {
            $errors[] = 'ECI must not exceed 2 characters';
        }

        return $errors;
    }

    /**
     * Check if network token data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
