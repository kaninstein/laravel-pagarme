<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class OrderItemDTO
{
    public function __construct(
        public int $amount,
        public string $description,
        public int $quantity,
        public ?string $code = null,
        public ?string $category = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'code' => $this->code,
            'category' => $this->category,
        ], fn ($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: $data['amount'],
            description: $data['description'],
            quantity: $data['quantity'],
            code: $data['code'] ?? null,
            category: $data['category'] ?? null,
        );
    }

    /**
     * Helper to create order item
     */
    public static function create(
        string $description,
        int $quantity,
        int $amount,
        ?string $code = null,
        ?string $category = null
    ): self {
        return new self(
            amount: $amount,
            description: $description,
            quantity: $quantity,
            code: $code,
            category: $category
        );
    }
}
