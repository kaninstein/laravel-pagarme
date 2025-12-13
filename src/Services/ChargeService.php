<?php

namespace Kaninstein\LaravelPagarme\Services;

use Kaninstein\LaravelPagarme\Client\PagarmeClient;

class ChargeService
{
    public function __construct(
        protected PagarmeClient $client
    ) {
    }

    /**
     * Get charge by ID
     */
    public function get(string $chargeId): array
    {
        return $this->client->get("charges/{$chargeId}");
    }

    /**
     * List all charges
     */
    public function list(array $params = []): array
    {
        return $this->client->get('charges', $params);
    }

    /**
     * Retry charge
     */
    public function retry(string $chargeId): array
    {
        return $this->client->post("charges/{$chargeId}/retry");
    }

    /**
     * Cancel charge
     */
    public function cancel(string $chargeId, array $data = []): array
    {
        return $this->client->delete("charges/{$chargeId}", $data);
    }

    /**
     * Capture charge
     */
    public function capture(string $chargeId, array $data = []): array
    {
        return $this->client->post("charges/{$chargeId}/capture", $data);
    }

    /**
     * Confirm cash payment
     *
     * For charges with payment method 'cash' and status 'pending'
     *
     * @param string $chargeId Charge ID
     * @param int|null $amount Amount to confirm in cents (null for full amount)
     * @param string|null $code Charge code to update (max 52 characters)
     * @param string|null $description Description
     * @return array
     */
    public function confirmCash(
        string $chargeId,
        ?int $amount = null,
        ?string $code = null,
        ?string $description = null
    ): array {
        $data = [];

        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        if ($code !== null) {
            $data['code'] = $code;
        }

        if ($description !== null) {
            $data['description'] = $description;
        }

        return $this->client->post("charges/{$chargeId}/confirm-payment", $data);
    }

    /**
     * Get charge transactions
     */
    public function transactions(string $chargeId): array
    {
        return $this->client->get("charges/{$chargeId}/transactions");
    }

    /**
     * Update charge card
     *
     * IMPORTANT: Can only be called when the card transaction was not authorized
     *
     * @param string $chargeId Charge ID
     * @param array $data Card data (card_id, card, card_token, initiated_type, recurrence_model, payment_origin)
     * @return array
     */
    public function updateCard(string $chargeId, array $data): array
    {
        return $this->client->patch("charges/{$chargeId}/card", $data);
    }

    /**
     * Update charge due date
     *
     * @param string $chargeId Charge ID
     * @param string $dueAt Due date (format: YYYY-MM-DD or datetime)
     * @return array
     */
    public function updateDueDate(string $chargeId, string $dueAt): array
    {
        return $this->client->patch("charges/{$chargeId}/due-date", [
            'due_at' => $dueAt,
        ]);
    }

    /**
     * Update charge payment method
     *
     * @param string $chargeId Charge ID
     * @param string $paymentMethod Payment method (credit_card, boleto, voucher, bank_transfer, pix)
     * @param array $paymentData Payment-specific data
     * @param bool $updateSubscription Whether to update subscription payment method
     * @param bool $reuseSplit Whether to reuse split rules
     * @return array
     */
    public function updatePaymentMethod(
        string $chargeId,
        string $paymentMethod,
        array $paymentData,
        bool $updateSubscription = false,
        bool $reuseSplit = false
    ): array {
        return $this->client->patch("charges/{$chargeId}/payment-method", [
            'payment_method' => $paymentMethod,
            $paymentMethod => $paymentData,
            'update_subscription' => $updateSubscription,
            'reuse_split' => $reuseSplit,
        ]);
    }

    /**
     * Cancel charge with partial amount
     *
     * @param string $chargeId Charge ID
     * @param int|null $amount Amount to cancel in cents (null for full amount)
     * @param array|null $bankAccount Bank account data for boleto refund (PSP only)
     * @return array
     */
    public function cancelWithAmount(string $chargeId, ?int $amount = null, ?array $bankAccount = null): array
    {
        $data = [];

        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        if ($bankAccount !== null) {
            $data['bank_account'] = $bankAccount;
        }

        return $this->client->delete("charges/{$chargeId}", $data);
    }

    /**
     * Refund boleto to bank account (PSP only)
     *
     * IMPORTANT: Refund must be to the same document as the sale
     *
     * @param string $chargeId Charge ID
     * @param array $bankAccount Bank account data
     * @param int|null $amount Amount to refund (null for full amount)
     * @return array
     */
    public function refundBoleto(string $chargeId, array $bankAccount, ?int $amount = null): array
    {
        return $this->cancelWithAmount($chargeId, $amount, $bankAccount);
    }

    /**
     * List charges with filters
     *
     * @param array $filters Filter options
     * @return array
     */
    public function search(array $filters = []): array
    {
        return $this->list($filters);
    }

    /**
     * Get charges by customer
     *
     * @param string $customerId Customer ID
     * @param int $page Page number
     * @param int $size Items per page
     * @return array
     */
    public function getByCustomer(string $customerId, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'customer_id' => $customerId,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Get charges by order
     *
     * @param string $orderId Order ID
     * @param int $page Page number
     * @param int $size Items per page
     * @return array
     */
    public function getByOrder(string $orderId, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'order_id' => $orderId,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Get charges by status
     *
     * @param string $status Status (pending, paid, canceled, processing, failed, overpaid, underpaid)
     * @param int $page Page number
     * @param int $size Items per page
     * @return array
     */
    public function getByStatus(string $status, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'status' => $status,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Get charges by payment method
     *
     * @param string $paymentMethod Payment method
     * @param int $page Page number
     * @param int $size Items per page
     * @return array
     */
    public function getByPaymentMethod(string $paymentMethod, int $page = 1, int $size = 10): array
    {
        return $this->list([
            'payment_method' => $paymentMethod,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Get charges by date range
     *
     * @param string $createdSince Start date (YYYY-MM-DD)
     * @param string $createdUntil End date (YYYY-MM-DD)
     * @param int $page Page number
     * @param int $size Items per page
     * @return array
     */
    public function getByDateRange(
        string $createdSince,
        string $createdUntil,
        int $page = 1,
        int $size = 10
    ): array {
        return $this->list([
            'created_since' => $createdSince,
            'created_until' => $createdUntil,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * Get charges by code (store reference)
     *
     * @param string $code Charge code
     * @return array
     */
    public function getByCode(string $code): array
    {
        return $this->list(['code' => $code]);
    }
}
