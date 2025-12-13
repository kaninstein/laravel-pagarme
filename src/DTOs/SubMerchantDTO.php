<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class SubMerchantDTO
{
    public function __construct(
        public string $merchantCategoryCode,
        public string $paymentFacilitatorCode,
        public string $code,
        public string $name,
        public string $document,
        public string $type = 'individual', // individual or company
        public ?string $legalName = null,
        public PhoneDTO|array|null $phone = null,
        public AddressDTO|array|null $address = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'merchant_category_code' => $this->merchantCategoryCode,
            'payment_facilitator_code' => $this->paymentFacilitatorCode,
            'code' => $this->code,
            'name' => $this->name,
            'document' => $this->document,
            'type' => $this->type,
        ];

        if ($this->legalName) {
            $data['legal_name'] = $this->legalName;
        }

        if ($this->phone) {
            $data['phone'] = $this->phone instanceof PhoneDTO
                ? $this->phone->toArray()
                : $this->phone;
        }

        if ($this->address) {
            $data['address'] = $this->address instanceof AddressDTO
                ? $this->address->toArray()
                : $this->address;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            merchantCategoryCode: $data['merchant_category_code'],
            paymentFacilitatorCode: $data['payment_facilitator_code'],
            code: $data['code'],
            name: $data['name'],
            document: $data['document'],
            type: $data['type'] ?? 'individual',
            legalName: $data['legal_name'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
        );
    }

    /**
     * Create from config values
     */
    public static function fromConfig(): ?self
    {
        if (!config('pagarme.submerchant.enabled', false)) {
            return null;
        }

        $configData = config('pagarme.submerchant');

        // Verificar se todos os campos obrigatórios estão preenchidos
        if (
            empty($configData['merchant_category_code']) ||
            empty($configData['payment_facilitator_code']) ||
            empty($configData['code']) ||
            empty($configData['name']) ||
            empty($configData['document'])
        ) {
            return null;
        }

        return new self(
            merchantCategoryCode: $configData['merchant_category_code'],
            paymentFacilitatorCode: $configData['payment_facilitator_code'],
            code: $configData['code'],
            name: $configData['name'],
            document: $configData['document'],
            type: $configData['type'] ?? 'individual',
            legalName: $configData['legal_name'] ?? null,
            phone: $configData['phone'] ?? null,
            address: $configData['address'] ?? null,
        );
    }

    /**
     * Create Brazilian submerchant helper
     */
    public static function brazilian(
        string $merchantCategoryCode,
        string $paymentFacilitatorCode,
        string $code,
        string $name,
        string $document,
        string $type = 'individual',
        ?string $legalName = null,
        ?PhoneDTO $phone = null,
        ?AddressDTO $address = null,
    ): self {
        return new self(
            merchantCategoryCode: $merchantCategoryCode,
            paymentFacilitatorCode: $paymentFacilitatorCode,
            code: $code,
            name: $name,
            document: $document,
            type: $type,
            legalName: $legalName,
            phone: $phone,
            address: $address,
        );
    }
}
