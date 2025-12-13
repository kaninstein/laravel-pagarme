<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Credit Card Payment Data Transfer Object
 *
 * For credit card payments with comprehensive options
 * Supports: regular cards, saved cards, tokenized cards, network tokens, Google Pay
 */
class CreditCardPaymentDTO
{
    public function __construct(
        public int $installments = 1,
        public ?string $statementDescriptor = null,
        public string $operationType = 'auth_and_capture',
        public CreditCardDTO|NetworkTokenDTO|string|null $card = null, // CreditCardDTO, NetworkTokenDTO, card_id, or card_token
        public ?string $networkToken = null,
        public ?string $recurrenceCycle = null,
        public ?int $merchantCategoryCode = null,
        public ?AuthenticationDTO $authentication = null,
        public ?bool $autoRecovery = null,
        public ?PayloadDTO $payload = null,
        public ?array $paymentType = null,
        public ?string $fundingSource = null,
        public ?string $initiatedType = null,
        public ?string $recurrenceModel = null,
        public ?string $channel = null,
        public ?bool $extendedLimitEnabled = null,
        public ?string $extendedLimitCode = null,
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

        $networkToken = null;
        if (isset($data['network_token'])) {
            $networkToken = is_array($data['network_token'])
                ? NetworkTokenDTO::fromArray($data['network_token'])
                : $data['network_token'];
        }

        $authentication = null;
        if (isset($data['authentication']) && is_array($data['authentication'])) {
            $authentication = AuthenticationDTO::fromArray($data['authentication']);
        }

        $payload = null;
        if (isset($data['payload']) && is_array($data['payload'])) {
            $payload = PayloadDTO::fromArray($data['payload']);
        }

        return new self(
            installments: $data['installments'] ?? 1,
            statementDescriptor: $data['statement_descriptor'] ?? null,
            operationType: $data['operation_type'] ?? 'auth_and_capture',
            card: $card,
            networkToken: $networkToken,
            recurrenceCycle: $data['recurrence_cycle'] ?? null,
            merchantCategoryCode: $data['merchant_category_code'] ?? null,
            authentication: $authentication,
            autoRecovery: $data['auto_recovery'] ?? null,
            payload: $payload,
            paymentType: $data['payment_type'] ?? null,
            fundingSource: $data['funding_source'] ?? null,
            initiatedType: $data['initiated_type'] ?? null,
            recurrenceModel: $data['recurrence_model'] ?? null,
            channel: $data['channel'] ?? null,
            extendedLimitEnabled: $data['extended_limit_enabled'] ?? null,
            extendedLimitCode: $data['extended_limit_code'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create credit card payment with card data
     */
    public static function withCard(
        CreditCardDTO $card,
        int $installments = 1,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            card: $card,
            installments: $installments,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Create credit card payment with saved card ID
     */
    public static function withCardId(
        string $cardId,
        int $installments = 1,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            card: $cardId,
            installments: $installments,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Create credit card payment with card token
     */
    public static function withCardToken(
        string $cardToken,
        int $installments = 1,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            card: $cardToken,
            installments: $installments,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Create credit card payment with network token
     */
    public static function withNetworkToken(
        NetworkTokenDTO $networkToken,
        int $installments = 1,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            networkToken: $networkToken,
            installments: $installments,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Create credit card payment with Google Pay
     */
    public static function withGooglePay(
        PayloadDTO $payload,
        int $installments = 1,
        ?string $statementDescriptor = null
    ): self {
        return new self(
            payload: $payload,
            installments: $installments,
            statementDescriptor: $statementDescriptor,
        );
    }

    /**
     * Set as first recurrence transaction
     */
    public function asFirstRecurrence(): self
    {
        $this->recurrenceCycle = 'first';
        return $this;
    }

    /**
     * Set as subsequent recurrence transaction
     * IMPORTANT: CVV should NOT be sent for subsequent transactions
     */
    public function asSubsequentRecurrence(): self
    {
        $this->recurrenceCycle = 'subsequent';
        return $this;
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
     * Set operation type to auth only (requires capture later)
     */
    public function authOnly(): self
    {
        $this->operationType = 'auth_only';
        return $this;
    }

    /**
     * Set operation type to pre-auth
     * IMPORTANT: Pre-authorization must be enabled by acquirer
     */
    public function preAuth(): self
    {
        $this->operationType = 'pre_auth';
        return $this;
    }

    /**
     * Set operation type to auth and capture immediately
     */
    public function authAndCapture(): self
    {
        $this->operationType = 'auth_and_capture';
        return $this;
    }

    /**
     * Enable super limit for private label cards
     */
    public function withExtendedLimit(string $code): self
    {
        $this->extendedLimitEnabled = true;
        $this->extendedLimitCode = $code;
        return $this;
    }

    /**
     * Set 3D Secure authentication
     */
    public function withAuthentication(AuthenticationDTO $authentication): self
    {
        $this->authentication = $authentication;
        return $this;
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'installments' => $this->installments,
            'operation_type' => $this->operationType,
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

        // Handle network token
        if ($this->networkToken instanceof NetworkTokenDTO) {
            $data['network_token'] = $this->networkToken->toArray();
        } elseif (is_string($this->networkToken)) {
            $data['network_token'] = $this->networkToken;
        }

        if ($this->recurrenceCycle !== null) {
            $data['recurrence_cycle'] = $this->recurrenceCycle;
        }

        if ($this->merchantCategoryCode !== null) {
            $data['merchant_category_code'] = $this->merchantCategoryCode;
        }

        if ($this->authentication !== null) {
            $data['authentication'] = $this->authentication->toArray();
        }

        if ($this->autoRecovery !== null) {
            $data['auto_recovery'] = $this->autoRecovery;
        }

        if ($this->payload !== null) {
            $data['payload'] = $this->payload->toArray();
        }

        if ($this->paymentType !== null) {
            $data['payment_type'] = $this->paymentType;
        }

        if ($this->fundingSource !== null) {
            $data['funding_source'] = $this->fundingSource;
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

        if ($this->extendedLimitEnabled !== null) {
            $data['extended_limit_enabled'] = $this->extendedLimitEnabled;
        }

        if ($this->extendedLimitCode !== null) {
            $data['extended_limit_code'] = $this->extendedLimitCode;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Validate credit card payment data
     */
    public function validate(): array
    {
        $errors = [];

        // Card validation
        if ($this->card === null && $this->networkToken === null && $this->payload === null) {
            $errors[] = 'Either card, card_id, card_token, network_token, or payload (Google Pay) must be provided';
        }

        // Installments validation
        if ($this->installments < 1) {
            $errors[] = 'Installments must be at least 1';
        }

        // Statement descriptor validation
        if ($this->statementDescriptor !== null) {
            // Max 13 for PSP, 22 for Gateway - using 22 as safe max
            if (strlen($this->statementDescriptor) > 22) {
                $errors[] = 'Statement descriptor must not exceed 22 characters (13 for PSP clients)';
            }
        }

        // Operation type validation
        $validOperationTypes = ['auth_and_capture', 'auth_only', 'pre_auth'];
        if (!in_array($this->operationType, $validOperationTypes)) {
            $errors[] = 'Operation type must be one of: ' . implode(', ', $validOperationTypes);
        }

        // Recurrence cycle validation
        $validRecurrenceCycles = ['first', 'subsequent'];
        if ($this->recurrenceCycle !== null && !in_array($this->recurrenceCycle, $validRecurrenceCycles)) {
            $errors[] = 'Recurrence cycle must be "first" or "subsequent"';
        }

        // CVV check for subsequent recurrence
        if ($this->recurrenceCycle === 'subsequent' && $this->card instanceof CreditCardDTO && $this->card->cvv) {
            $errors[] = 'CVV should not be sent for subsequent recurrence transactions';
        }

        // Funding source validation
        $validFundingSources = ['credit', 'debit', 'prepaid'];
        if ($this->fundingSource !== null && !in_array($this->fundingSource, $validFundingSources)) {
            $errors[] = 'Funding source must be one of: ' . implode(', ', $validFundingSources);
        }

        // Initiated type validation
        $validInitiatedTypes = ['partial_shipment', 'related_or_delayed_charge', 'no_show', 'retry'];
        if ($this->initiatedType !== null && !in_array($this->initiatedType, $validInitiatedTypes)) {
            $errors[] = 'Initiated type must be one of: ' . implode(', ', $validInitiatedTypes);
        }

        // Recurrence model validation
        $validRecurrenceModels = ['standing_order', 'instalment', 'subscription'];
        if ($this->recurrenceModel !== null && !in_array($this->recurrenceModel, $validRecurrenceModels)) {
            $errors[] = 'Recurrence model must be one of: ' . implode(', ', $validRecurrenceModels);
        }

        // Channel validation
        if ($this->channel !== null && $this->channel !== 'payment_link') {
            $errors[] = 'Channel must be "payment_link"';
        }

        // Extended limit validation
        if ($this->extendedLimitEnabled && empty($this->extendedLimitCode)) {
            $errors[] = 'Extended limit code is required when extended limit is enabled';
        }

        // Validate card if it's a DTO
        if ($this->card instanceof CreditCardDTO) {
            $cardErrors = $this->card->validate();
            $errors = array_merge($errors, $cardErrors);
        }

        // Validate network token if provided
        if ($this->networkToken instanceof NetworkTokenDTO) {
            $networkTokenErrors = $this->networkToken->validate();
            $errors = array_merge($errors, $networkTokenErrors);
        }

        // Validate authentication if provided
        if ($this->authentication !== null) {
            $authErrors = $this->authentication->validate();
            $errors = array_merge($errors, $authErrors);
        }

        // Validate payload if provided
        if ($this->payload !== null) {
            $payloadErrors = $this->payload->validate();
            $errors = array_merge($errors, $payloadErrors);
        }

        return $errors;
    }

    /**
     * Check if credit card payment data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
