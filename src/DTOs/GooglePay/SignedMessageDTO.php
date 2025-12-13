<?php

namespace Kaninstein\LaravelPagarme\DTOs\GooglePay;

/**
 * Signed Message Data Transfer Object
 *
 * Part of Google Pay token structure
 */
class SignedMessageDTO
{
    public function __construct(
        public string $encryptedMessage,
        public string $ephemeralPublicKey,
        public string $tag,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            encryptedMessage: $data['encryptedMessage'],
            ephemeralPublicKey: $data['ephemeralPublicKey'],
            tag: $data['tag'],
        );
    }

    /**
     * Create from JSON string (as received from Google Pay)
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return self::fromArray($data);
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        return [
            'encryptedMessage' => $this->encryptedMessage,
            'ephemeralPublicKey' => $this->ephemeralPublicKey,
            'tag' => $this->tag,
        ];
    }
}
