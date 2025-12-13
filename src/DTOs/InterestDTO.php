<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Interest Data Transfer Object
 *
 * For late payment interest on boletos
 * Only available for PSP clients (not Gateway)
 *
 * Types:
 * - flat: Fixed amount in cents (charged daily)
 * - percentage: Percentage value (charged monthly)
 */
class InterestDTO
{
    public function __construct(
        public int $days,
        public string $type,
        public float $amount,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            days: $data['days'],
            type: $data['type'],
            amount: $data['amount'],
        );
    }

    /**
     * Create flat interest (fixed amount in cents, charged daily)
     */
    public static function flat(int $days, int $amountInCents): self
    {
        return new self(
            days: $days,
            type: 'flat',
            amount: $amountInCents,
        );
    }

    /**
     * Create percentage interest (charged monthly)
     * @param int $days Days after expiration to start charging
     * @param float $percentage Percentage value (e.g., 1.5 for 1.5%)
     */
    public static function percentage(int $days, float $percentage): self
    {
        return new self(
            days: $days,
            type: 'percentage',
            amount: $percentage,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        return [
            'days' => $this->days,
            'type' => $this->type,
            'amount' => $this->amount,
        ];
    }

    /**
     * Validate interest data
     */
    public function validate(): array
    {
        $errors = [];

        // Days validation
        if ($this->days < 1) {
            $errors[] = 'Days must be at least 1';
        }

        // Type validation
        if (!in_array($this->type, ['flat', 'percentage'])) {
            $errors[] = 'Type must be "flat" or "percentage"';
        }

        // Amount validation based on type
        if ($this->type === 'flat' && $this->amount < 1) {
            $errors[] = 'Flat amount must be at least 1 cent';
        }

        if ($this->type === 'percentage') {
            if ($this->amount <= 0) {
                $errors[] = 'Percentage must be greater than 0';
            }
            if ($this->amount >= 100) {
                $errors[] = 'Percentage must be less than 100';
            }
        }

        return $errors;
    }

    /**
     * Check if interest data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
