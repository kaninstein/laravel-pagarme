<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class AddressDTO
{
    public function __construct(
        public string $line1,
        public string $zipCode,
        public string $city,
        public string $state,
        public string $country,
        public ?string $line2 = null,
        public ?array $metadata = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'line_1' => $this->line1,
            'line_2' => $this->line2,
            'zip_code' => $this->zipCode,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            line1: $data['line_1'],
            zipCode: $data['zip_code'],
            city: $data['city'],
            state: $data['state'],
            country: $data['country'],
            line2: $data['line_2'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Validate address data according to Pagarme API
     */
    public function validate(): array
    {
        $errors = [];

        if (strlen($this->line1) > 256) {
            $errors['line_1'] = 'Line 1 must be max 256 characters';
        }

        if ($this->line2 && strlen($this->line2) > 128) {
            $errors['line_2'] = 'Line 2 must be max 128 characters';
        }

        if (strlen($this->zipCode) > 16) {
            $errors['zip_code'] = 'ZIP code must be max 16 characters';
        }

        if (strlen($this->city) > 64) {
            $errors['city'] = 'City must be max 64 characters';
        }

        // Verificar se zip_code é numérico
        if (!preg_match('/^\d+$/', $this->zipCode)) {
            $errors['zip_code'] = 'ZIP code must contain only numbers';
        }

        return $errors;
    }

    /**
     * Check if address is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Create Brazilian address with proper line_1 format
     *
     * IMPORTANT: line_1 must follow the format: "Number, Street, Neighborhood"
     * (in this order, separated by commas)
     *
     * @param string $number Street number (can be empty)
     * @param string $street Street name
     * @param string $neighborhood Neighborhood
     * @param string $zipCode CEP (only numbers)
     * @param string $city City name
     * @param string $state State code (ISO 3166-2: SP, RJ, etc.)
     * @param string|null $complement Complement (floor, apt, room, etc.)
     * @return self
     */
    public static function brazilian(
        string $number,
        string $street,
        string $neighborhood,
        string $zipCode,
        string $city,
        string $state,
        ?string $complement = null,
        ?array $metadata = null
    ): self {
        // Format line_1: "Number, Street, Neighborhood"
        $parts = array_filter([$number, $street, $neighborhood]);
        $line1 = implode(', ', $parts);

        // Remove non-numeric characters from CEP
        $zipCode = self::formatCep($zipCode);

        return new self(
            line1: $line1,
            zipCode: $zipCode,
            city: $city,
            state: $state,
            country: 'BR',
            line2: $complement,
            metadata: $metadata,
        );
    }

    /**
     * Create international address
     */
    public static function international(
        string $line1,
        string $zipCode,
        string $city,
        string $state,
        string $countryCode,
        ?string $line2 = null,
        ?array $metadata = null
    ): self {
        return new self(
            line1: $line1,
            zipCode: $zipCode,
            city: $city,
            state: $state,
            country: $countryCode,
            line2: $line2,
            metadata: $metadata,
        );
    }

    /**
     * Parse address from old format (street, number, neighborhood)
     * WARNING: This format will be discontinued by Pagarme!
     * Use brazilian() method instead.
     *
     * @deprecated Use brazilian() method instead
     */
    public static function fromLegacyFormat(
        string $street,
        string $number,
        string $neighborhood,
        string $zipCode,
        string $city,
        string $state,
        ?string $complement = null
    ): self {
        return self::brazilian(
            number: $number,
            street: $street,
            neighborhood: $neighborhood,
            zipCode: $zipCode,
            city: $city,
            state: $state,
            complement: $complement
        );
    }

    /**
     * Format Brazilian CEP (remove non-numeric characters)
     */
    public static function formatCep(string $cep): string
    {
        return preg_replace('/\D/', '', $cep);
    }

    /**
     * Parse line_1 into components
     * Returns array with ['number', 'street', 'neighborhood']
     */
    public function parseLine1(): array
    {
        $parts = explode(',', $this->line1);
        $parts = array_map('trim', $parts);

        return [
            'number' => $parts[0] ?? '',
            'street' => $parts[1] ?? '',
            'neighborhood' => $parts[2] ?? '',
        ];
    }

    /**
     * Helper to create address for billing (useful for credit cards)
     */
    public static function forBilling(
        string $number,
        string $street,
        string $neighborhood,
        string $zipCode,
        string $city,
        string $state,
        string $country = 'BR'
    ): self {
        if ($country === 'BR') {
            return self::brazilian(
                number: $number,
                street: $street,
                neighborhood: $neighborhood,
                zipCode: $zipCode,
                city: $city,
                state: $state
            );
        }

        return self::international(
            line1: "{$number}, {$street}, {$neighborhood}",
            zipCode: $zipCode,
            city: $city,
            state: $state,
            countryCode: $country
        );
    }
}
