<?php

namespace Kaninstein\LaravelPagarme\Exceptions;

use Kaninstein\LaravelPagarme\Enums\AbecsReturnCode;
use Illuminate\Http\Client\Response;

/**
 * Exception thrown when a transaction is declined
 */
class TransactionDeclinedException extends PagarmeException
{
    protected ?AbecsReturnCode $abecsCode = null;
    protected ?string $acquirerReturnCode = null;
    protected ?string $gatewayReturnCode = null;
    protected ?string $acquirerMessage = null;
    protected ?string $gatewayMessage = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Response $response = null,
        ?string $acquirerReturnCode = null,
        ?string $gatewayReturnCode = null
    ) {
        parent::__construct($message, $code, $response);

        $this->acquirerReturnCode = $acquirerReturnCode;
        $this->gatewayReturnCode = $gatewayReturnCode;

        // Try to map to ABECS code
        if ($acquirerReturnCode) {
            $this->abecsCode = AbecsReturnCode::tryFrom($acquirerReturnCode);
        }

        $this->parseDeclineInfo();
    }

    /**
     * Create from charge response
     */
    public static function fromCharge(array $charge): self
    {
        $lastTransaction = $charge['last_transaction'] ?? [];

        $acquirerReturnCode = $lastTransaction['acquirer_return_code'] ?? null;
        $gatewayReturnCode = $lastTransaction['gateway_response_code'] ?? null;

        $message = $lastTransaction['acquirer_message']
            ?? $lastTransaction['gateway_response']
            ?? 'Transaction declined';

        $exception = new self(
            message: $message,
            code: 0,
            response: null,
            acquirerReturnCode: $acquirerReturnCode,
            gatewayReturnCode: $gatewayReturnCode
        );

        $exception->acquirerMessage = $lastTransaction['acquirer_message'] ?? null;
        $exception->gatewayMessage = $lastTransaction['gateway_response'] ?? null;

        return $exception;
    }

    /**
     * Parse decline information from response
     */
    protected function parseDeclineInfo(): void
    {
        if (!$this->response) {
            return;
        }

        $body = $this->response->json();

        // Try to extract charge information
        $charges = $body['charges'] ?? [];
        if (!empty($charges)) {
            $lastCharge = $charges[0];
            $lastTransaction = $lastCharge['last_transaction'] ?? [];

            $this->acquirerReturnCode = $lastTransaction['acquirer_return_code'] ?? null;
            $this->gatewayReturnCode = $lastTransaction['gateway_response_code'] ?? null;
            $this->acquirerMessage = $lastTransaction['acquirer_message'] ?? null;
            $this->gatewayMessage = $lastTransaction['gateway_response'] ?? null;

            if ($this->acquirerReturnCode) {
                $this->abecsCode = AbecsReturnCode::tryFrom($this->acquirerReturnCode);
            }
        }
    }

    /**
     * Get ABECS return code
     */
    public function getAbecsCode(): ?AbecsReturnCode
    {
        return $this->abecsCode;
    }

    /**
     * Get acquirer return code
     */
    public function getAcquirerReturnCode(): ?string
    {
        return $this->acquirerReturnCode;
    }

    /**
     * Get gateway return code
     */
    public function getGatewayReturnCode(): ?string
    {
        return $this->gatewayReturnCode;
    }

    /**
     * Get acquirer message
     */
    public function getAcquirerMessage(): ?string
    {
        return $this->acquirerMessage;
    }

    /**
     * Get gateway message
     */
    public function getGatewayMessage(): ?string
    {
        return $this->gatewayMessage;
    }

    /**
     * Get human-readable decline reason
     */
    public function getDeclineReason(): string
    {
        if ($this->abecsCode) {
            return $this->abecsCode->getMessage();
        }

        return $this->acquirerMessage
            ?? $this->gatewayMessage
            ?? $this->getMessage();
    }

    /**
     * Check if transaction can be retried
     */
    public function canRetry(): bool
    {
        if ($this->abecsCode) {
            return $this->abecsCode->canRetry();
        }

        // If we don't have ABECS code, be conservative
        return false;
    }

    /**
     * Get full decline information
     */
    public function getDeclineInfo(): array
    {
        return [
            'abecs_code' => $this->abecsCode?->value,
            'abecs_message' => $this->abecsCode?->getMessage(),
            'acquirer_code' => $this->acquirerReturnCode,
            'acquirer_message' => $this->acquirerMessage,
            'gateway_code' => $this->gatewayReturnCode,
            'gateway_message' => $this->gatewayMessage,
            'can_retry' => $this->canRetry(),
            'is_fraud' => $this->isFraudRelated(),
        ];
    }

    /**
     * Check if decline is fraud-related
     */
    public function isFraudRelated(): bool
    {
        if (!$this->abecsCode) {
            return false;
        }

        return in_array($this->abecsCode, [
            AbecsReturnCode::DECLINED_SUSPECTED_FRAUD,
            AbecsReturnCode::DECLINED_CONFIRMED_FRAUD,
            AbecsReturnCode::DECLINED_ANTIFRAUD_REJECTED,
        ]);
    }

    /**
     * Check if decline is due to insufficient funds
     */
    public function isInsufficientFunds(): bool
    {
        return $this->abecsCode === AbecsReturnCode::DECLINED_INSUFFICIENT_FUNDS;
    }

    /**
     * Check if decline is due to invalid card
     */
    public function isInvalidCard(): bool
    {
        if (!$this->abecsCode) {
            return false;
        }

        return in_array($this->abecsCode, [
            AbecsReturnCode::DECLINED_INVALID_CARD,
            AbecsReturnCode::DECLINED_EXPIRED_CARD,
            AbecsReturnCode::DECLINED_BLOCKED_CARD,
            AbecsReturnCode::DECLINED_LOST_CARD,
            AbecsReturnCode::DECLINED_STOLEN_CARD,
        ]);
    }
}
