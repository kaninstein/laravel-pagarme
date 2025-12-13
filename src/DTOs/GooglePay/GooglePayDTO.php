<?php

namespace Kaninstein\LaravelPagarme\DTOs\GooglePay;

/**
 * Google Pay Data Transfer Object
 *
 * For Google Pay payment processing
 *
 * IMPORTANT: Do not manipulate the fields received from Google Pay API
 * All fields are mandatory
 */
class GooglePayDTO
{
    public function __construct(
        public string $signature,
        public IntermediateSigningKeyDTO $intermediateSigningKey,
        public string $version,
        public SignedMessageDTO|string $signedMessage,
        public string $merchantIdentifier,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $intermediateSigningKey = $data['intermediate_signing_key'] instanceof IntermediateSigningKeyDTO
            ? $data['intermediate_signing_key']
            : IntermediateSigningKeyDTO::fromArray($data['intermediate_signing_key']);

        $signedMessage = $data['signed_message'];
        if (is_array($signedMessage)) {
            $signedMessage = SignedMessageDTO::fromArray($signedMessage);
        } elseif (is_string($signedMessage) && !str_starts_with($signedMessage, '{')) {
            // It's already a JSON string, keep as is
        }

        return new self(
            signature: $data['signature'],
            intermediateSigningKey: $intermediateSigningKey,
            version: $data['version'],
            signedMessage: $signedMessage,
            merchantIdentifier: $data['merchant_identifier'],
        );
    }

    /**
     * Create from Google Pay token response
     * Expects the token structure as returned by Google Pay API
     */
    public static function fromGooglePayToken(array $token, string $merchantIdentifier): self
    {
        $intermediateSigningKey = IntermediateSigningKeyDTO::fromArray($token['intermediateSigningKey']);

        return new self(
            signature: $token['signature'],
            intermediateSigningKey: $intermediateSigningKey,
            version: $token['protocolVersion'],
            signedMessage: $token['signedMessage'], // Keep as JSON string
            merchantIdentifier: $merchantIdentifier,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        return [
            'signature' => $this->signature,
            'intermediate_signing_key' => $this->intermediateSigningKey->toArray(),
            'version' => $this->version,
            'signed_message' => $this->signedMessage instanceof SignedMessageDTO
                ? json_encode($this->signedMessage->toArray())
                : $this->signedMessage,
            'merchant_identifier' => $this->merchantIdentifier,
        ];
    }

    /**
     * Validate Google Pay data
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->signature)) {
            $errors[] = 'Signature is required';
        }

        if (empty($this->version)) {
            $errors[] = 'Version is required';
        }

        if ($this->version !== 'ECv2') {
            $errors[] = 'Only ECv2 protocol version is supported';
        }

        if (empty($this->merchantIdentifier)) {
            $errors[] = 'Merchant identifier is required';
        }

        if (empty($this->signedMessage)) {
            $errors[] = 'Signed message is required';
        }

        return $errors;
    }

    /**
     * Check if Google Pay data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
