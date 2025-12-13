<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * 3D Secure Authentication Data Transfer Object
 *
 * Used for credit and debit card authentication
 * Supports 3DS versions 2.1.0 and 2.2.0 (version 1.0 is deprecated)
 */
class ThreeDSecureDTO
{
    public function __construct(
        public string $mpi,
        public string $eci,
        public string $cavv,
        public string $transactionId,
        public ?string $dsTransactionId = null,
        public ?string $version = null,
        public ?string $successUrl = null, // For debit card only
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mpi: $data['mpi'],
            eci: $data['eci'],
            cavv: $data['cavv'],
            transactionId: $data['transaction_id'],
            dsTransactionId: $data['ds_transaction_id'] ?? null,
            version: $data['version'] ?? null,
            successUrl: $data['success_url'] ?? null,
        );
    }

    /**
     * Create for third party authenticator
     */
    public static function thirdParty(
        string $eci,
        string $cavv,
        string $transactionId,
        ?string $dsTransactionId = null,
        string $version = '2.2.0',
        ?string $successUrl = null
    ): self {
        return new self(
            mpi: 'third_party',
            eci: $eci,
            cavv: $cavv,
            transactionId: $transactionId,
            dsTransactionId: $dsTransactionId,
            version: $version,
            successUrl: $successUrl,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'mpi' => $this->mpi,
            'eci' => $this->eci,
            'cavv' => $this->cavv,
            'transaction_id' => $this->transactionId,
        ];

        if ($this->dsTransactionId) {
            $data['ds_transaction_id'] = $this->dsTransactionId;
        }

        if ($this->version) {
            $data['version'] = $this->version;
        }

        if ($this->successUrl) {
            $data['success_url'] = $this->successUrl;
        }

        return $data;
    }

    /**
     * Validate 3DS data
     */
    public function validate(): array
    {
        $errors = [];

        // MPI validation
        if (strlen($this->mpi) > 11) {
            $errors[] = 'MPI must not exceed 11 characters';
        }

        if (!in_array($this->mpi, ['third_party'])) {
            $errors[] = 'MPI must be "third_party"';
        }

        // ECI validation (2 characters max)
        if (strlen($this->eci) > 2) {
            $errors[] = 'ECI must not exceed 2 characters';
        }

        // CAVV validation (256 characters max)
        if (strlen($this->cavv) > 256) {
            $errors[] = 'CAVV must not exceed 256 characters';
        }

        // Transaction ID validation (256 characters max)
        if (strlen($this->transactionId) > 256) {
            $errors[] = 'Transaction ID must not exceed 256 characters';
        }

        // DS Transaction ID validation (256 characters max)
        if ($this->dsTransactionId && strlen($this->dsTransactionId) > 256) {
            $errors[] = 'DS Transaction ID must not exceed 256 characters';
        }

        // Version validation (6 characters max)
        if ($this->version && strlen($this->version) > 6) {
            $errors[] = 'Version must not exceed 6 characters';
        }

        // Version format validation
        if ($this->version && !in_array($this->version, ['2.1.0', '2.2.0', '1.0'])) {
            $errors[] = 'Version must be 2.1.0, 2.2.0, or 1.0 (deprecated)';
        }

        // Success URL validation (512 characters max) - for debit card
        if ($this->successUrl && strlen($this->successUrl) > 512) {
            $errors[] = 'Success URL must not exceed 512 characters';
        }

        return $errors;
    }

    /**
     * Check if 3DS data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
