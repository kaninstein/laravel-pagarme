<?php

namespace Kaninstein\LaravelPagarme\DTOs;

use DateTime;

/**
 * Boleto Payment Data Transfer Object
 *
 * For boleto (bank slip) payments
 *
 * Supported banks:
 * - 001: Banco do Brasil
 * - 033: Santander
 * - 104: Caixa Econômica Federal
 * - 197: Banco Stone
 * - 237: Bradesco
 * - 341: Itaú
 * - 745: Citibank
 *
 * IMPORTANT: For registered boletos, customer name, address, and document are MANDATORY
 */
class BoletoPaymentDTO
{
    public function __construct(
        public ?string $bank = null,
        public ?string $instructions = null,
        public ?DateTime $dueAt = null,
        public ?string $nossoNumero = null,
        public ?string $type = null,
        public ?string $documentNumber = null,
        public ?string $statementDescriptor = null,
        public ?InterestDTO $interest = null,
        public ?FineDTO $fine = null,
        public ?array $metadata = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $dueAt = null;
        if (isset($data['due_at'])) {
            $dueAt = is_string($data['due_at'])
                ? new DateTime($data['due_at'])
                : $data['due_at'];
        }

        $interest = null;
        if (isset($data['interest']) && is_array($data['interest'])) {
            $interest = InterestDTO::fromArray($data['interest']);
        }

        $fine = null;
        if (isset($data['fine']) && is_array($data['fine'])) {
            $fine = FineDTO::fromArray($data['fine']);
        }

        return new self(
            bank: $data['bank'] ?? null,
            instructions: $data['instructions'] ?? null,
            dueAt: $dueAt,
            nossoNumero: $data['nosso_numero'] ?? null,
            type: $data['type'] ?? null,
            documentNumber: $data['document_number'] ?? null,
            statementDescriptor: $data['statement_descriptor'] ?? null,
            interest: $interest,
            fine: $fine,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create basic boleto
     */
    public static function create(
        DateTime $dueAt,
        ?string $instructions = null,
        ?string $bank = null
    ): self {
        return new self(
            dueAt: $dueAt,
            instructions: $instructions,
            bank: $bank,
        );
    }

    /**
     * Create boleto with interest and fine (PSP only)
     */
    public static function withInterestAndFine(
        DateTime $dueAt,
        InterestDTO $interest,
        FineDTO $fine,
        ?string $instructions = null,
        ?string $bank = null
    ): self {
        return new self(
            dueAt: $dueAt,
            interest: $interest,
            fine: $fine,
            instructions: $instructions,
            bank: $bank,
        );
    }

    /**
     * Set boleto type (DM = Duplicata Mercantil, BDP = Boleto de Proposta)
     */
    public function withType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set document number
     */
    public function withDocumentNumber(string $documentNumber): self
    {
        $this->documentNumber = $documentNumber;
        return $this;
    }

    /**
     * Set nosso número (unique boleto identifier)
     */
    public function withNossoNumero(string $nossoNumero): self
    {
        $this->nossoNumero = $nossoNumero;
        return $this;
    }

    /**
     * Set statement descriptor
     */
    public function withStatementDescriptor(string $descriptor): self
    {
        $this->statementDescriptor = $descriptor;
        return $this;
    }

    /**
     * Set metadata
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->bank !== null) {
            $data['bank'] = $this->bank;
        }

        if ($this->instructions !== null) {
            $data['instructions'] = $this->instructions;
        }

        if ($this->dueAt !== null) {
            $data['due_at'] = $this->dueAt->format('Y-m-d\TH:i:s\Z');
        }

        if ($this->nossoNumero !== null) {
            $data['nosso_numero'] = $this->nossoNumero;
        }

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        if ($this->documentNumber !== null) {
            $data['document_number'] = $this->documentNumber;
        }

        if ($this->statementDescriptor !== null) {
            $data['statement_descriptor'] = $this->statementDescriptor;
        }

        if ($this->interest !== null) {
            $data['interest'] = $this->interest->toArray();
        }

        if ($this->fine !== null) {
            $data['fine'] = $this->fine->toArray();
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Validate boleto payment data
     */
    public function validate(): array
    {
        $errors = [];

        // Bank validation
        $validBanks = ['001', '033', '104', '197', '237', '341', '745'];
        if ($this->bank !== null && !in_array($this->bank, $validBanks)) {
            $errors[] = 'Invalid bank code. Valid codes: ' . implode(', ', $validBanks);
        }

        // Instructions max length
        if ($this->instructions !== null && strlen($this->instructions) > 256) {
            $errors[] = 'Instructions must not exceed 256 characters';
        }

        // Type validation
        if ($this->type !== null && !in_array($this->type, ['DM', 'BDP'])) {
            $errors[] = 'Type must be DM (Duplicata Mercantil) or BDP (Boleto de Proposta)';
        }

        // Document number max length
        if ($this->documentNumber !== null && strlen($this->documentNumber) > 16) {
            $errors[] = 'Document number must not exceed 16 characters';
        }

        // Statement descriptor max length
        if ($this->statementDescriptor !== null && strlen($this->statementDescriptor) > 13) {
            $errors[] = 'Statement descriptor must not exceed 13 characters';
        }

        // Validate interest
        if ($this->interest !== null) {
            $interestErrors = $this->interest->validate();
            $errors = array_merge($errors, $interestErrors);
        }

        // Validate fine
        if ($this->fine !== null) {
            $fineErrors = $this->fine->validate();
            $errors = array_merge($errors, $fineErrors);
        }

        return $errors;
    }

    /**
     * Check if boleto payment data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Get list of supported banks
     */
    public static function getSupportedBanks(): array
    {
        return [
            '001' => 'Banco do Brasil',
            '033' => 'Santander',
            '104' => 'Caixa Econômica Federal',
            '197' => 'Banco Stone',
            '237' => 'Bradesco',
            '341' => 'Itaú',
            '745' => 'Citibank',
        ];
    }
}
