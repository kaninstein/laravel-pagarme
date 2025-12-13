<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class PhonesDTO
{
    public function __construct(
        public ?PhoneDTO $homePhone = null,
        public ?PhoneDTO $mobilePhone = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'home_phone' => $this->homePhone?->toArray(),
            'mobile_phone' => $this->mobilePhone?->toArray(),
        ], fn ($value) => $value !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            homePhone: isset($data['home_phone'])
                ? PhoneDTO::fromArray($data['home_phone'])
                : null,
            mobilePhone: isset($data['mobile_phone'])
                ? PhoneDTO::fromArray($data['mobile_phone'])
                : null,
        );
    }

    /**
     * Create with only mobile phone
     */
    public static function mobile(PhoneDTO $phone): self
    {
        return new self(mobilePhone: $phone);
    }

    /**
     * Create with only home phone
     */
    public static function home(PhoneDTO $phone): self
    {
        return new self(homePhone: $phone);
    }

    /**
     * Create with both phones
     */
    public static function both(PhoneDTO $homePhone, PhoneDTO $mobilePhone): self
    {
        return new self(
            homePhone: $homePhone,
            mobilePhone: $mobilePhone
        );
    }

    /**
     * Helper for Brazilian phone numbers
     */
    public static function brazilian(
        ?PhoneDTO $mobilePhone = null,
        ?PhoneDTO $homePhone = null
    ): self {
        return new self(
            homePhone: $homePhone,
            mobilePhone: $mobilePhone
        );
    }
}
