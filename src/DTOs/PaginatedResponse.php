<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class PaginatedResponse
{
    public function __construct(
        public array $data,
        public int $total,
        public ?string $previous = null,
        public ?string $next = null,
    ) {
    }

    public static function fromArray(array $response): self
    {
        return new self(
            data: $response['data'] ?? [],
            total: $response['paging']['total'] ?? 0,
            previous: $response['paging']['previous'] ?? null,
            next: $response['paging']['next'] ?? null,
        );
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'paging' => [
                'total' => $this->total,
                'previous' => $this->previous,
                'next' => $this->next,
            ],
        ];
    }
}
