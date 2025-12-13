<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Debit Card Payment Data Transfer Object
 *
 * For debit card payments
 * Only available for Gateway clients
 *
 * IMPORTANT: Debit card transactions are usually authenticated with 3DS
 */
class DebitCardPaymentDTO
{
    public function __construct(
        public ?string $statementDescriptor = null,
        public CreditCardDTO|string|null $card = null, // CreditCardDTO, card_id, or card_token
        public ?string $networkToken = null,
        public bool $recurrence = false,
        public ?int $merchantCategoryCode = null,
        public ?AuthenticationDTO $authentication = null,
        public ?array $payload = null,
        public ?string $initiatedType = null,
        public ?string $recurrenceModel = null,
        public ?string $channel = null,
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

        $authentication = null;
        if (isset($data['authentication']) && is_array($data['authentication'])) {
            $authentication = AuthenticationDTO::fromArray($data['authentication']);
        }

        return new self(
            statementDescriptor: $data['statement_descriptor'] ?? null,
            card: $card,
            networkToken: $data['network_token'] ?? null,
            recurrence: $data['recurrence'] ?? false,
            merchantCategoryCode: $data['merchant_category_code'] ?? null,
            authentication: $authentication,
            payload: $data['payload'] ?? null,
            initiatedType: $data['initiated_type'] ?? null,
            recurrenceModel: $data['recurrence_model'] ?? null,
            channel: $data['channel'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create debit card payment with card data
     */
    public static function withCard(
        CreditCardDTO $card,
        ?AuthenticationDTO $authentication = null,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            card: $card,
            authentication: $authentication,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Create debit card payment with card ID
     */
    public static function withCardId(
        string $cardId,
        ?AuthenticationDTO $authentication = null,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            card: $cardId,
            authentication: $authentication,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Create debit card payment with card token
     */
    public static function withCardToken(
        string $cardToken,
        ?AuthenticationDTO $authentication = null,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            card: $cardToken,
            authentication: $authentication,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Set as payment link transaction (required for Elo from Oct 17, 2025)
     */
    public function asPaymentLink(): self
    {
        $this->channel = 'payment_link';
        return $this;
    }

    /**
     * Set recurrence
     */
    public function withRecurrence(bool $recurrence, ?string $recurrenceModel = null): self
    {
        $this->recurrence = $recurrence;
        if ($recurrenceModel) {
            $this->recurrenceModel = $recurrenceModel;
        }
        return $this;
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'recurrence' => $this->recurrence,
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

        if ($this->networkToken !== null) {
            $data['network_token'] = $this->networkToken;
        }

        if ($this->merchantCategoryCode !== null) {
            $data['merchant_category_code'] = $this->merchantCategoryCode;
        }

        if ($this->authentication !== null) {
            $data['authentication'] = $this->authentication->toArray();
        }

        if ($this->payload !== null) {
            $data['payload'] = $this->payload;
        }

        if ($this->initiatedType !== null) {
            $data['initiated_type'] = $this->initiatedType;
        }

        if ($this->recurrenceModel !== null) {
            $data['recurrence_model'] = $this->recurrenceModel;
        }

        if ($this->channel !== null) {
            $data['channel'] = $this->channel;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Validate debit card payment data
     */
    public function validate(): array
    {
        $errors = [];

        // Card validation
        if ($this->card === null && $this->networkToken === null) {
            $errors[] = 'Either card, card_id, card_token, or network_token must be provided';
        }

        // Statement descriptor max length
        if ($this->statementDescriptor !== null && strlen($this->statementDescriptor) > 22) {
            $errors[] = 'Statement descriptor must not exceed 22 characters';
        }

        // Validate initiated type
        $validInitiatedTypes = ['partial_shipment', 'related_or_delayed_charge', 'no_show', 'retry'];
        if ($this->initiatedType !== null && !in_array($this->initiatedType, $validInitiatedTypes)) {
            $errors[] = 'Initiated type must be one of: ' . implode(', ', $validInitiatedTypes);
        }

        // Validate recurrence model
        $validRecurrenceModels = ['standing_order', 'instalment', 'subscription'];
        if ($this->recurrenceModel !== null && !in_array($this->recurrenceModel, $validRecurrenceModels)) {
            $errors[] = 'Recurrence model must be one of: ' . implode(', ', $validRecurrenceModels);
        }

        // Validate channel
        if ($this->channel !== null && $this->channel !== 'payment_link') {
            $errors[] = 'Channel must be "payment_link"';
        }

        // Validate authentication if provided
        if ($this->authentication !== null && $this->card instanceof CreditCardDTO) {
            $authErrors = $this->authentication->validate();
            $errors = array_merge($errors, $authErrors);
        }

        // Validate card if it's a DTO
        if ($this->card instanceof CreditCardDTO) {
            $cardErrors = $this->card->validate();
            $errors = array_merge($errors, $cardErrors);
        }

        return $errors;
    }

    /**
     * Check if debit card payment data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
