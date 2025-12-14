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

    protected function resolveCode(): string
    {
        if (!empty($this->code)) {
            return $this->code;
        }

        $base = mb_strtolower($this->description);
        $base = preg_replace('/[^a-z0-9]+/i', '-', $base) ?? 'item';
        $base = trim($base, '-');

        if ($base === '') {
            $base = 'item';
        }

        $hash = substr(sha1($this->description . '|' . $this->amount . '|' . $this->quantity), 0, 10);
        $code = "item-{$base}-{$hash}";

        if (strlen($code) > 52) {
            $code = substr($code, 0, 52);
        }

        return $code;
    }

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'code' => $this->resolveCode(),
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
