<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class CreditCardDTO
{
    public function __construct(
        public ?string $number = null,
        public ?string $holderName = null,
        public ?string $holderDocument = null,
        public ?int $expMonth = null,
        public ?int $expYear = null,
        public ?string $cvv = null,
        public ?string $brand = null,
        public ?string $cardId = null, // For saved cards
        public AddressDTO|array|null $billingAddress = null,
        public ?string $billingAddressId = null,
        public ?string $label = null,
        public ?string $token = null, // For tokenized cards
        public ?array $metadata = null,
        public ?array $options = null,
        public string $type = 'credit', // credit or voucher
        public bool $privateLabel = false,
    ) {
    }

    public function toArray(): array
    {
        // If using saved card by ID
        if ($this->cardId) {
            return array_filter([
                'card_id' => $this->cardId,
                'options' => $this->options,
            ], fn ($value) => $value !== null);
        }

        // If using token
        if ($this->token) {
            return array_filter([
                'token' => $this->token,
                'billing_address' => $this->billingAddress instanceof AddressDTO
                    ? $this->billingAddress->toArray()
                    : $this->billingAddress,
                'billing_address_id' => $this->billingAddressId,
                'metadata' => $this->metadata,
            ], fn ($value) => $value !== null);
        }

        // New card
        return array_filter([
            'number' => $this->number,
            'holder_name' => $this->holderName,
            'holder_document' => $this->holderDocument,
            'exp_month' => $this->expMonth,
            'exp_year' => $this->expYear,
            'cvv' => $this->cvv,
            'brand' => $this->brand,
            'billing_address' => $this->billingAddress instanceof AddressDTO
                ? $this->billingAddress->toArray()
                : $this->billingAddress,
            'billing_address_id' => $this->billingAddressId,
            'label' => $this->label,
            'metadata' => $this->metadata,
            'options' => $this->options,
            'type' => $this->type,
            'private_label' => $this->privateLabel,
        ], fn ($value) => $value !== null);
    }

    public static function fromCardId(string $cardId, ?array $options = null): self
    {
        return new self(cardId: $cardId, options: $options);
    }

    public static function fromToken(string $token, AddressDTO|array|null $billingAddress = null): self
    {
        return new self(token: $token, billingAddress: $billingAddress);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            number: $data['number'] ?? null,
            holderName: $data['holder_name'] ?? null,
            holderDocument: $data['holder_document'] ?? null,
            expMonth: $data['exp_month'] ?? null,
            expYear: $data['exp_year'] ?? null,
            cvv: $data['cvv'] ?? null,
            brand: $data['brand'] ?? null,
            cardId: $data['card_id'] ?? null,
            billingAddress: $data['billing_address'] ?? null,
            billingAddressId: $data['billing_address_id'] ?? null,
            label: $data['label'] ?? null,
            token: $data['token'] ?? null,
            metadata: $data['metadata'] ?? null,
            options: $data['options'] ?? null,
            type: $data['type'] ?? 'credit',
            privateLabel: $data['private_label'] ?? false,
        );
    }

    /**
     * Validate card data
     */
    public function validate(): array
    {
        $errors = [];

        // Se estiver usando card_id ou token, não precisa validar dados do cartão
        if ($this->cardId || $this->token) {
            return $errors;
        }

        if (!$this->number) {
            $errors['number'] = 'Card number is required';
        } elseif (strlen($this->number) < 13 || strlen($this->number) > 19) {
            $errors['number'] = 'Card number must be between 13 and 19 characters';
        }

        if (!$this->holderName) {
            $errors['holder_name'] = 'Holder name is required';
        } elseif (strlen($this->holderName) > 64) {
            $errors['holder_name'] = 'Holder name must be max 64 characters';
        }

        // Holder document obrigatório para voucher
        if ($this->type === 'voucher' && !$this->holderDocument) {
            $errors['holder_document'] = 'Holder document is required for voucher cards';
        }

        if (!$this->expMonth) {
            $errors['exp_month'] = 'Expiration month is required';
        } elseif ($this->expMonth < 1 || $this->expMonth > 12) {
            $errors['exp_month'] = 'Expiration month must be between 1 and 12';
        }

        if (!$this->expYear) {
            $errors['exp_year'] = 'Expiration year is required';
        }

        if (!$this->cvv) {
            $errors['cvv'] = 'CVV is required';
        } elseif (!in_array(strlen($this->cvv), [3, 4])) {
            $errors['cvv'] = 'CVV must be 3 or 4 characters';
        }

        // Brand obrigatório para private label
        if ($this->privateLabel && !$this->brand) {
            $errors['brand'] = 'Brand is required for private label cards';
        }

        return $errors;
    }

    /**
     * Check if card is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
