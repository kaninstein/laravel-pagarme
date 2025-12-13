<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Additional Information Data Transfer Object
 *
 * Used for PIX payments to add extra information
 * that will be visible to the consumer during payment
 */
class AdditionalInformationDTO
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            value: $data['value'],
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }

    /**
     * Create from key-value pair
     */
    public static function create(string $name, string $value): self
    {
        return new self(name: $name, value: $value);
    }
}
