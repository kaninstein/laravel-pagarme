<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class PhoneDTO
{
    public function __construct(
        public string $countryCode,
        public string $areaCode,
        public string $number,
    ) {
    }

    public function toArray(): array
    {
        return [
            'country_code' => $this->countryCode,
            'area_code' => $this->areaCode,
            'number' => $this->number,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: $data['country_code'],
            areaCode: $data['area_code'],
            number: $data['number'],
        );
    }

    /**
     * Create Brazilian phone (country code 55)
     */
    public static function brazilian(string $areaCode, string $number): self
    {
        return new self(
            countryCode: '55',
            areaCode: $areaCode,
            number: $number,
        );
    }

    /**
     * Parse Brazilian phone from string format
     * Examples: (11) 98765-4321, 11987654321, +5511987654321
     */
    public static function parseBrazilian(string $phone): self
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // Remove country code if present
        if (str_starts_with($cleaned, '55') && strlen($cleaned) > 11) {
            $cleaned = substr($cleaned, 2);
        }

        // Extract area code (first 2 digits) and number
        $areaCode = substr($cleaned, 0, 2);
        $number = substr($cleaned, 2);

        return self::brazilian($areaCode, $number);
    }
}
