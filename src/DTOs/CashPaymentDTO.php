<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Cash Payment Data Transfer Object
 *
 * For cash payments
 */
class CashPaymentDTO
{
    public function __construct(
        public ?string $description = null,
        public bool $confirm = false,
        public ?array $metadata = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'] ?? null,
            confirm: $data['confirm'] ?? false,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create cash payment
     */
    public static function create(
        string $description,
        bool $confirm = false,
        ?array $metadata = null
    ): self {
        return new self(
            description: $description,
            confirm: $confirm,
            metadata: $metadata,
        );
    }

    /**
     * Create with auto-confirm
     */
    public static function withAutoConfirm(
        string $description,
        ?array $metadata = null
    ): self {
        return new self(
            description: $description,
            confirm: true,
            metadata: $metadata,
        );
    }

    /**
     * Create without auto-confirm (manual confirmation required)
     */
    public static function withManualConfirm(
        string $description,
        ?array $metadata = null
    ): self {
        return new self(
            description: $description,
            confirm: false,
            metadata: $metadata,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'confirm' => $this->confirm,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Validate cash payment data
     */
    public function validate(): array
    {
        $errors = [];

        // Description max length
        if ($this->description !== null && strlen($this->description) > 256) {
            $errors[] = 'Description must not exceed 256 characters';
        }

        return $errors;
    }

    /**
     * Check if cash payment data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
