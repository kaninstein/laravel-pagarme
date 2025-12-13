<?php

namespace Kaninstein\LaravelPagarme\DTOs;

use DateTime;

/**
 * PIX Payment Data Transfer Object
 *
 * For PIX payments with QR Code
 * Maximum expiration: 10 years
 *
 * IMPORTANT: Customer must include name, email, document, and phones
 */
class PixPaymentDTO
{
    public function __construct(
        public ?int $expiresIn = null,
        public ?DateTime $expiresAt = null,
        public ?array $additionalInformation = null, // Array of AdditionalInformationDTO
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $additionalInfo = null;
        if (isset($data['additional_information']) && is_array($data['additional_information'])) {
            $additionalInfo = array_map(
                fn($item) => AdditionalInformationDTO::fromArray($item),
                $data['additional_information']
            );
        }

        $expiresAt = null;
        if (isset($data['expires_at'])) {
            $expiresAt = is_string($data['expires_at'])
                ? new DateTime($data['expires_at'])
                : $data['expires_at'];
        }

        return new self(
            expiresIn: $data['expires_in'] ?? null,
            expiresAt: $expiresAt,
            additionalInformation: $additionalInfo,
        );
    }

    /**
     * Create PIX payment with expiration in seconds
     */
    public static function withExpiresIn(
        int $expiresIn,
        ?array $additionalInformation = null
    ): self {
        return new self(
            expiresIn: $expiresIn,
            additionalInformation: $additionalInformation,
        );
    }

    /**
     * Create PIX payment with expiration datetime
     */
    public static function withExpiresAt(
        DateTime $expiresAt,
        ?array $additionalInformation = null
    ): self {
        return new self(
            expiresAt: $expiresAt,
            additionalInformation: $additionalInformation,
        );
    }

    /**
     * Add additional information
     */
    public function addAdditionalInformation(string $name, string $value): self
    {
        if ($this->additionalInformation === null) {
            $this->additionalInformation = [];
        }

        $this->additionalInformation[] = AdditionalInformationDTO::create($name, $value);

        return $this;
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->expiresIn !== null) {
            $data['expires_in'] = (string) $this->expiresIn;
        }

        if ($this->expiresAt !== null) {
            $data['expires_at'] = $this->expiresAt->format('Y-m-d\TH:i:s');
        }

        if ($this->additionalInformation !== null && !empty($this->additionalInformation)) {
            $data['additional_information'] = array_map(
                fn($info) => $info instanceof AdditionalInformationDTO
                    ? $info->toArray()
                    : $info,
                $this->additionalInformation
            );
        }

        return $data;
    }

    /**
     * Validate PIX payment data
     */
    public function validate(): array
    {
        $errors = [];

        // Either expires_in or expires_at must be provided
        if ($this->expiresIn === null && $this->expiresAt === null) {
            $errors[] = 'Either expires_in or expires_at must be provided';
        }

        // Validate expires_in
        if ($this->expiresIn !== null && $this->expiresIn <= 0) {
            $errors[] = 'Expires in must be greater than 0';
        }

        // Validate expires_at (max 10 years in the future)
        if ($this->expiresAt !== null) {
            $now = new DateTime();
            $maxExpiration = (clone $now)->modify('+10 years');

            if ($this->expiresAt <= $now) {
                $errors[] = 'Expiration date must be in the future';
            }

            if ($this->expiresAt > $maxExpiration) {
                $errors[] = 'Maximum expiration is 10 years';
            }
        }

        return $errors;
    }

    /**
     * Check if PIX payment data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
