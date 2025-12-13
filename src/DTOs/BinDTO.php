<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class BinDTO
{
    public function __construct(
        public string $brand,
        public array $gaps,
        public array $lengths,
        public string $mask,
        public int $cvv,
        public string $brandImage,
        public array $possibleBrands,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            brand: $data['brand'] ?? '',
            gaps: $data['gaps'] ?? [],
            lengths: $data['lengths'] ?? [],
            mask: $data['mask'] ?? '',
            cvv: $data['cvv'] ?? 3,
            brandImage: $data['brandImage'] ?? '',
            possibleBrands: $data['possibleBrands'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'brand' => $this->brand,
            'gaps' => $this->gaps,
            'lengths' => $this->lengths,
            'mask' => $this->mask,
            'cvv' => $this->cvv,
            'brandImage' => $this->brandImage,
            'possibleBrands' => $this->possibleBrands,
        ];
    }

    /**
     * Get card number length for this BIN
     */
    public function getCardLength(): int
    {
        return $this->lengths[0] ?? 16;
    }

    /**
     * Check if multiple brands are possible
     */
    public function hasMultipleBrands(): bool
    {
        return count($this->possibleBrands) > 1;
    }

    /**
     * Format card number with gaps
     */
    public function formatCardNumber(string $cardNumber): string
    {
        $formatted = '';
        $position = 0;

        foreach ($this->gaps as $gap) {
            $formatted .= substr($cardNumber, $position, $gap);
            $position += $gap;

            if ($position < strlen($cardNumber)) {
                $formatted .= ' ';
            }
        }

        // Add remaining digits
        if ($position < strlen($cardNumber)) {
            $formatted .= substr($cardNumber, $position);
        }

        return trim($formatted);
    }

    /**
     * Get brand display name
     */
    public function getBrandDisplayName(): string
    {
        return match (strtolower($this->brand)) {
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'elo' => 'Elo',
            'amex' => 'American Express',
            'jcb' => 'JCB',
            'aura' => 'Aura',
            'hipercard' => 'Hipercard',
            'diners' => 'Diners Club',
            'discover' => 'Discover',
            'unionpay' => 'UnionPay',
            default => ucfirst($this->brand),
        };
    }
}
