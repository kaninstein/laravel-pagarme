<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Fine Data Transfer Object
 *
 * For late payment fine on boletos
 * Only available for PSP clients (not Gateway)
 *
 * Types:
 * - flat: Fixed amount in cents
 * - percentage: Percentage value of order amount
 */
class FineDTO
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
     * Create flat fine (fixed amount in cents)
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
     * Create percentage fine
     * @param int $days Days after expiration to charge fine
     * @param float $percentage Percentage value (e.g., 2.0 for 2%)
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
     * Validate fine data
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
     * Check if fine data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
