<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Private Label Card Payment Data Transfer Object
 *
 * For private label card payments
 *
 * IMPORTANT: This product is temporarily suspended for new activations
 */
class PrivateLabelPaymentDTO
{
    public function __construct(
        public int $installments = 1,
        public ?string $statementDescriptor = null,
        public bool $capture = true,
        public CreditCardDTO|string|null $card = null, // CreditCardDTO, card_id, or card_token
        public ?array $metadata = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $card = null;
        if (isset($data['card'])) {
            $card = is_array($data['card'])
                ? CreditCardDTO::fromArray($data['card'])
                : $data['card'];
        } elseif (isset($data['card_id'])) {
            $card = $data['card_id'];
        } elseif (isset($data['card_token'])) {
            $card = $data['card_token'];
        }

        return new self(
            installments: $data['installments'] ?? 1,
            statementDescriptor: $data['statement_descriptor'] ?? null,
            capture: $data['capture'] ?? true,
            card: $card,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create private label payment with card data
     */
    public static function withCard(
        CreditCardDTO $card,
        int $installments = 1,
        bool $capture = true,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            installments: $installments,
            statementDescriptor: $statementDescriptor,
            capture: $capture,
            card: $card,
        );
    }

    /**
     * Create private label payment with card ID
     */
    public static function withCardId(
        string $cardId,
        int $installments = 1,
        bool $capture = true,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            installments: $installments,
            statementDescriptor: $statementDescriptor,
            capture: $capture,
            card: $cardId,
        );
    }

    /**
     * Create private label payment with card token
     */
    public static function withCardToken(
        string $cardToken,
        int $installments = 1,
        bool $capture = true,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            installments: $installments,
            statementDescriptor: $statementDescriptor,
            capture: $capture,
            card: $cardToken,
        );
    }

    /**
     * Set to auth only (capture later)
     */
    public function authOnly(): self
    {
        $this->capture = false;
        return $this;
    }

    /**
     * Set to auth and capture immediately
     */
    public function authAndCapture(): self
    {
        $this->capture = true;
        return $this;
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'installments' => $this->installments,
            'capture' => $this->capture,
        ];

        if ($this->statementDescriptor !== null) {
            $data['statement_descriptor'] = $this->statementDescriptor;
        }

        // Handle card
        if ($this->card instanceof CreditCardDTO) {
            $data['card'] = $this->card->toArray();
        } elseif (is_string($this->card)) {
            // Check if it's card_id or card_token
            if (str_starts_with($this->card, 'card_')) {
                $data['card_id'] = $this->card;
            } else {
                $data['card_token'] = $this->card;
            }
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Validate private label payment data
     */
    public function validate(): array
    {
        $errors = [];

        // Card validation
        if ($this->card === null) {
            $errors[] = 'Card, card_id, or card_token must be provided';
        }

        // Installments validation
        if ($this->installments < 1) {
            $errors[] = 'Installments must be at least 1';
        }

        // Statement descriptor max length
        if ($this->statementDescriptor !== null && strlen($this->statementDescriptor) > 22) {
            $errors[] = 'Statement descriptor must not exceed 22 characters';
        }

        // Validate card if it's a DTO
        if ($this->card instanceof CreditCardDTO) {
            // Private label must have brand
            if ($this->card->privateLabel && empty($this->card->brand)) {
                $errors[] = 'Private label cards must have brand specified';
            }

            $cardErrors = $this->card->validate();
            $errors = array_merge($errors, $cardErrors);
        }

        return $errors;
    }

    /**
     * Check if private label payment data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
