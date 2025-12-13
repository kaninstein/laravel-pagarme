<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class CustomerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $type = 'individual', // individual or company
        public ?string $document = null,
        public ?string $documentType = null, // CPF, CNPJ or PASSPORT
        public PhonesDTO|array|null $phones = null,
        public ?string $code = null,
        public AddressDTO|array|null $address = null,
        public ?array $metadata = null,
        public ?string $gender = null, // male or female
        public ?string $birthdate = null, // mm/dd/yyyy format
        public ?int $fbId = null,
        public ?string $fbAccessToken = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'document' => $this->document,
            'document_type' => $this->documentType,
            'phones' => $this->phones instanceof PhonesDTO
                ? $this->phones->toArray()
                : $this->phones,
            'code' => $this->code,
            'address' => $this->address instanceof AddressDTO
                ? $this->address->toArray()
                : $this->address,
            'metadata' => $this->metadata,
            'gender' => $this->gender,
            'birthdate' => $this->birthdate,
            'fb_id' => $this->fbId,
            'fb_access_token' => $this->fbAccessToken,
        ], fn ($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            type: $data['type'] ?? 'individual',
            document: $data['document'] ?? null,
            documentType: $data['document_type'] ?? null,
            phones: $data['phones'] ?? null,
            code: $data['code'] ?? null,
            address: $data['address'] ?? null,
            metadata: $data['metadata'] ?? null,
            gender: $data['gender'] ?? null,
            birthdate: $data['birthdate'] ?? null,
            fbId: $data['fb_id'] ?? null,
            fbAccessToken: $data['fb_access_token'] ?? null,
        );
    }

    /**
     * Validate field lengths according to Pagarme API
     */
    public function validate(): array
    {
        $errors = [];

        if (strlen($this->name) > 64) {
            $errors['name'] = 'Name must be max 64 characters';
        }

        if (strlen($this->email) > 64) {
            $errors['email'] = 'Email must be max 64 characters';
        }

        if ($this->code && strlen($this->code) > 52) {
            $errors['code'] = 'Code must be max 52 characters';
        }

        if ($this->document && $this->documentType) {
            $maxLength = match ($this->documentType) {
                'CPF', 'CNPJ' => 16,
                'PASSPORT' => 50,
                default => 16,
            };

            if (strlen($this->document) > $maxLength) {
                $errors['document'] = "Document must be max {$maxLength} characters for {$this->documentType}";
            }
        }

        if ($this->gender && !in_array($this->gender, ['male', 'female'])) {
            $errors['gender'] = 'Gender must be "male" or "female"';
        }

        if ($this->type && !in_array($this->type, ['individual', 'company'])) {
            $errors['type'] = 'Type must be "individual" or "company"';
        }

        if ($this->documentType && !in_array($this->documentType, ['CPF', 'CNPJ', 'PASSPORT'])) {
            $errors['document_type'] = 'Document type must be "CPF", "CNPJ" or "PASSPORT"';
        }

        return $errors;
    }

    /**
     * Check if customer data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Helper to create individual customer (pessoa física)
     */
    public static function individual(
        string $name,
        string $email,
        string $cpf,
        ?PhonesDTO $phone = null,
        ?AddressDTO $address = null,
    ): self {
        return new self(
            name: $name,
            email: $email,
            type: 'individual',
            document: $cpf,
            documentType: 'CPF',
            phones: $phone,
            address: $address,
        );
    }

    /**
     * Helper to create company customer (pessoa jurídica)
     */
    public static function company(
        string $name,
        string $email,
        string $cnpj,
        ?PhonesDTO $phone = null,
        ?AddressDTO $address = null,
    ): self {
        return new self(
            name: $name,
            email: $email,
            type: 'company',
            document: $cnpj,
            documentType: 'CNPJ',
            phones: $phone,
            address: $address,
        );
    }
}
