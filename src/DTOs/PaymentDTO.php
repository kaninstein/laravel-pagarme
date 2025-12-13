<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Payment Data Transfer Object
 *
 * Generic payment container for all payment methods
 *
 * Supported payment methods:
 * - credit_card
 * - debit_card
 * - pix
 * - boleto
 * - voucher
 * - cash
 * - safetypay
 * - private_label
 * - bank_transfer
 * - checkout
 */
class PaymentDTO
{
    public function __construct(
        public string $paymentMethod,
        public ?int $amount = null, // For multi-payment scenarios
        public ?array $metadata = null,
        public ?CustomerDTO $customer = null, // For multi-buyer scenarios
        public ?CreditCardPaymentDTO $creditCard = null,
        public ?DebitCardPaymentDTO $debitCard = null,
        public ?PixPaymentDTO $pix = null,
        public ?BoletoPaymentDTO $boleto = null,
        public ?VoucherPaymentDTO $voucher = null,
        public ?CashPaymentDTO $cash = null,
        public ?SafetyPayPaymentDTO $safetypay = null,
        public ?PrivateLabelPaymentDTO $privateLabel = null,
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $customer = null;
        if (isset($data['customer']) && is_array($data['customer'])) {
            $customer = CustomerDTO::fromArray($data['customer']);
        }

        $creditCard = null;
        if (isset($data['credit_card']) && is_array($data['credit_card'])) {
            $creditCard = CreditCardPaymentDTO::fromArray($data['credit_card']);
        }

        $debitCard = null;
        if (isset($data['debit_card']) && is_array($data['debit_card'])) {
            $debitCard = DebitCardPaymentDTO::fromArray($data['debit_card']);
        }

        $pix = null;
        if (isset($data['pix']) && is_array($data['pix'])) {
            $pix = PixPaymentDTO::fromArray($data['pix']);
        }

        $boleto = null;
        if (isset($data['boleto']) && is_array($data['boleto'])) {
            $boleto = BoletoPaymentDTO::fromArray($data['boleto']);
        }

        $voucher = null;
        if (isset($data['voucher']) && is_array($data['voucher'])) {
            $voucher = VoucherPaymentDTO::fromArray($data['voucher']);
        }

        $cash = null;
        if (isset($data['cash']) && is_array($data['cash'])) {
            $cash = CashPaymentDTO::fromArray($data['cash']);
        }

        $safetypay = null;
        if (isset($data['safetypay']) && is_array($data['safetypay'])) {
            $safetypay = SafetyPayPaymentDTO::fromArray($data['safetypay']);
        }

        $privateLabel = null;
        if (isset($data['private_label']) && is_array($data['private_label'])) {
            $privateLabel = PrivateLabelPaymentDTO::fromArray($data['private_label']);
        }

        return new self(
            paymentMethod: $data['payment_method'],
            amount: $data['amount'] ?? null,
            metadata: $data['metadata'] ?? null,
            customer: $customer,
            creditCard: $creditCard,
            debitCard: $debitCard,
            pix: $pix,
            boleto: $boleto,
            voucher: $voucher,
            cash: $cash,
            safetypay: $safetypay,
            privateLabel: $privateLabel,
        );
    }

    /**
     * Create credit card payment
     */
    public static function creditCard(
        CreditCardPaymentDTO $creditCard,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'credit_card',
            amount: $amount,
            customer: $customer,
            creditCard: $creditCard,
        );
    }

    /**
     * Create debit card payment
     */
    public static function debitCard(
        DebitCardPaymentDTO $debitCard,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'debit_card',
            amount: $amount,
            customer: $customer,
            debitCard: $debitCard,
        );
    }

    /**
     * Create PIX payment
     */
    public static function pix(
        PixPaymentDTO $pix,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'pix',
            amount: $amount,
            customer: $customer,
            pix: $pix,
        );
    }

    /**
     * Create boleto payment
     */
    public static function boleto(
        BoletoPaymentDTO $boleto,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'boleto',
            amount: $amount,
            customer: $customer,
            boleto: $boleto,
        );
    }

    /**
     * Create voucher payment
     */
    public static function voucher(
        VoucherPaymentDTO $voucher,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'voucher',
            amount: $amount,
            customer: $customer,
            voucher: $voucher,
        );
    }

    /**
     * Create cash payment
     */
    public static function cash(
        CashPaymentDTO $cash,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'cash',
            amount: $amount,
            customer: $customer,
            cash: $cash,
        );
    }

    /**
     * Create SafetyPay payment
     */
    public static function safetypay(
        ?SafetyPayPaymentDTO $safetypay = null,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'safetypay',
            amount: $amount,
            customer: $customer,
            safetypay: $safetypay ?? SafetyPayPaymentDTO::create(),
        );
    }

    /**
     * Create private label payment
     */
    public static function privateLabel(
        PrivateLabelPaymentDTO $privateLabel,
        ?int $amount = null,
        ?CustomerDTO $customer = null
    ): self {
        return new self(
            paymentMethod: 'private_label',
            amount: $amount,
            customer: $customer,
            privateLabel: $privateLabel,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $data = [
            'payment_method' => $this->paymentMethod,
        ];

        if ($this->amount !== null) {
            $data['amount'] = $this->amount;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        if ($this->customer !== null) {
            $data['customer'] = $this->customer->toArray();
        }

        // Add payment-specific data based on payment method
        match ($this->paymentMethod) {
            'credit_card' => $data['credit_card'] = $this->creditCard?->toArray(),
            'debit_card' => $data['debit_card'] = $this->debitCard?->toArray(),
            'pix' => $data['pix'] = $this->pix?->toArray(),
            'boleto' => $data['boleto'] = $this->boleto?->toArray(),
            'voucher' => $data['voucher'] = $this->voucher?->toArray(),
            'cash' => $data['cash'] = $this->cash?->toArray(),
            'safetypay' => $data['safetypay'] = $this->safetypay?->toArray() ?? [],
            'private_label' => $data['private_label'] = $this->privateLabel?->toArray(),
            default => null,
        };

        return array_filter($data, fn($value) => $value !== null);
    }

    /**
     * Validate payment data
     */
    public function validate(): array
    {
        $errors = [];

        // Payment method validation
        $validMethods = [
            'credit_card',
            'debit_card',
            'pix',
            'boleto',
            'voucher',
            'cash',
            'safetypay',
            'private_label',
            'bank_transfer',
            'checkout',
        ];

        if (!in_array($this->paymentMethod, $validMethods)) {
            $errors[] = 'Invalid payment method. Valid methods: ' . implode(', ', $validMethods);
        }

        // Validate that payment-specific data is provided
        $paymentDataProvided = match ($this->paymentMethod) {
            'credit_card' => $this->creditCard !== null,
            'debit_card' => $this->debitCard !== null,
            'pix' => $this->pix !== null,
            'boleto' => $this->boleto !== null,
            'voucher' => $this->voucher !== null,
            'cash' => $this->cash !== null,
            'safetypay' => true, // SafetyPay doesn't require specific data
            'private_label' => $this->privateLabel !== null,
            default => false,
        };

        if (!$paymentDataProvided) {
            $errors[] = "Payment data for {$this->paymentMethod} is required";
        }

        // Validate payment-specific data
        match ($this->paymentMethod) {
            'credit_card' => $this->creditCard && ($errors = array_merge($errors, $this->creditCard->validate())),
            'debit_card' => $this->debitCard && ($errors = array_merge($errors, $this->debitCard->validate())),
            'pix' => $this->pix && ($errors = array_merge($errors, $this->pix->validate())),
            'boleto' => $this->boleto && ($errors = array_merge($errors, $this->boleto->validate())),
            'voucher' => $this->voucher && ($errors = array_merge($errors, $this->voucher->validate())),
            'cash' => $this->cash && ($errors = array_merge($errors, $this->cash->validate())),
            'private_label' => $this->privateLabel && ($errors = array_merge($errors, $this->privateLabel->validate())),
            default => null,
        };

        // Validate customer if provided
        if ($this->customer !== null) {
            $customerErrors = $this->customer->validate();
            $errors = array_merge($errors, $customerErrors);
        }

        // Amount validation
        if ($this->amount !== null && $this->amount <= 0) {
            $errors[] = 'Amount must be greater than 0';
        }

        return $errors;
    }

    /**
     * Check if payment data is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Get supported payment methods
     */
    public static function getSupportedMethods(): array
    {
        return [
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'pix' => 'PIX',
            'boleto' => 'Boleto (Bank Slip)',
            'voucher' => 'Voucher (VR, Pluxee, Ticket)',
            'cash' => 'Cash',
            'safetypay' => 'SafetyPay',
            'private_label' => 'Private Label Card',
            'bank_transfer' => 'Bank Transfer',
            'checkout' => 'Checkout',
        ];
    }
}
