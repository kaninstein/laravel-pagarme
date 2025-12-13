<?php

namespace Kaninstein\LaravelPagarme\DTOs;

class OrderDTO
{
    /**
     * @param OrderItemDTO[] $items
     * @param PaymentDTO[] $payments
     */
    public function __construct(
        public array $items,
        public CustomerDTO|string $customer,
        public array $payments,
        public ?string $code = null,
        public ?bool $closed = null,
        public ?bool $antifraudEnabled = null,
        public ?string $ip = null,
        public ?string $sessionId = null,
        public ?array $location = null,
        public ?array $device = null,
        public ?array $metadata = null,
        public SubMerchantDTO|array|null $submerchant = null,
        public ?array $shipping = null,
    ) {
    }

    public function toArray(): array
    {
        $data = array_filter([
            'items' => array_map(
                fn (OrderItemDTO $item) => $item->toArray(),
                $this->items
            ),
            'customer' => is_string($this->customer)
                ? ['id' => $this->customer]
                : $this->customer->toArray(),
            'payments' => array_map(
                fn (PaymentDTO $payment) => $payment->toArray(),
                $this->payments
            ),
            'code' => $this->code,
            'closed' => $this->closed,
            'antifraud_enabled' => $this->antifraudEnabled,
            'ip' => $this->ip,
            'session_id' => $this->sessionId,
            'location' => $this->location,
            'device' => $this->device,
            'metadata' => $this->metadata,
            'shipping' => $this->shipping,
        ], fn ($value) => $value !== null);

        // Add submerchant if provided or from config
        $submerchant = $this->resolveSubmerchant();
        if ($submerchant) {
            $data['submerchant'] = $submerchant;
        }

        return $data;
    }

    /**
     * Resolve submerchant data from instance or config
     */
    protected function resolveSubmerchant(): ?array
    {
        // If explicitly set to null, don't use submerchant
        if ($this->submerchant === null && func_num_args() > 0) {
            return null;
        }

        // If submerchant is provided as DTO
        if ($this->submerchant instanceof SubMerchantDTO) {
            return $this->submerchant->toArray();
        }

        // If submerchant is provided as array
        if (is_array($this->submerchant)) {
            return $this->submerchant;
        }

        // Try to get from config if enabled
        $configSubmerchant = SubMerchantDTO::fromConfig();
        return $configSubmerchant?->toArray();
    }

    /**
     * Disable submerchant for this order (even if enabled in config)
     */
    public function withoutSubmerchant(): self
    {
        $this->submerchant = null;
        return $this;
    }

    /**
     * Set submerchant for this order
     */
    public function withSubmerchant(SubMerchantDTO|array $submerchant): self
    {
        $this->submerchant = $submerchant;
        return $this;
    }

    /**
     * Create order from array
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(
                fn($item) => $item instanceof OrderItemDTO ? $item : OrderItemDTO::fromArray($item),
                $data['items']
            );
        }

        $customer = $data['customer'];
        if (isset($data['customer_id'])) {
            $customer = $data['customer_id'];
        } elseif (is_array($data['customer'])) {
            $customer = CustomerDTO::fromArray($data['customer']);
        }

        $payments = [];
        if (isset($data['payments']) && is_array($data['payments'])) {
            $payments = array_map(
                fn($payment) => $payment instanceof PaymentDTO ? $payment : PaymentDTO::fromArray($payment),
                $data['payments']
            );
        }

        $submerchant = null;
        if (isset($data['submerchant'])) {
            $submerchant = is_array($data['submerchant'])
                ? SubMerchantDTO::fromArray($data['submerchant'])
                : $data['submerchant'];
        }

        return new self(
            items: $items,
            customer: $customer,
            payments: $payments,
            code: $data['code'] ?? null,
            closed: $data['closed'] ?? null,
            antifraudEnabled: $data['antifraud_enabled'] ?? null,
            ip: $data['ip'] ?? null,
            sessionId: $data['session_id'] ?? null,
            location: $data['location'] ?? null,
            device: $data['device'] ?? null,
            metadata: $data['metadata'] ?? null,
            submerchant: $submerchant,
            shipping: $data['shipping'] ?? null,
        );
    }

    /**
     * Create a simple order with single payment
     */
    public static function create(
        array $items,
        CustomerDTO|string $customer,
        PaymentDTO $payment,
        ?string $code = null,
        bool $closed = true
    ): self {
        return new self(
            items: $items,
            customer: $customer,
            payments: [$payment],
            code: $code,
            closed: $closed,
        );
    }

    /**
     * Create an open order (can be modified after creation)
     */
    public static function createOpen(
        array $items,
        CustomerDTO|string $customer,
        array $payments,
        ?string $code = null
    ): self {
        return new self(
            items: $items,
            customer: $customer,
            payments: $payments,
            code: $code,
            closed: false,
        );
    }

    /**
     * Create a closed order (cannot be modified after creation)
     */
    public static function createClosed(
        array $items,
        CustomerDTO|string $customer,
        array $payments,
        ?string $code = null
    ): self {
        return new self(
            items: $items,
            customer: $customer,
            payments: $payments,
            code: $code,
            closed: true,
        );
    }

    /**
     * Add payment to order
     */
    public function addPayment(PaymentDTO $payment): self
    {
        $this->payments[] = $payment;
        return $this;
    }

    /**
     * Add item to order
     */
    public function addItem(OrderItemDTO $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Set as closed order
     */
    public function close(): self
    {
        $this->closed = true;
        return $this;
    }

    /**
     * Set as open order
     */
    public function open(): self
    {
        $this->closed = false;
        return $this;
    }

    /**
     * Get total order amount (sum of all payments)
     */
    public function getTotalAmount(): int
    {
        return array_reduce(
            $this->payments,
            fn($total, PaymentDTO $payment) => $total + ($payment->amount ?? 0),
            0
        );
    }

    /**
     * Enable antifraud for this order (Gateway clients only)
     */
    public function withAntifraud(bool $enabled = true): self
    {
        $this->antifraudEnabled = $enabled;
        return $this;
    }

    /**
     * Set IP address
     */
    public function withIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Set session ID
     */
    public function withSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * Set shipping data
     */
    public function withShipping(array $shipping): self
    {
        $this->shipping = $shipping;
        return $this;
    }

    /**
     * Set location data
     */
    public function withLocation(array $location): self
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Set device data
     */
    public function withDevice(array $device): self
    {
        $this->device = $device;
        return $this;
    }

    /**
     * Validate order data
     */
    public function validate(): array
    {
        $errors = [];

        // Items validation
        if (empty($this->items)) {
            $errors[] = 'Order must have at least one item';
        }

        // Customer validation
        if (is_string($this->customer) && empty($this->customer)) {
            $errors[] = 'Customer ID cannot be empty';
        } elseif ($this->customer instanceof CustomerDTO) {
            $customerErrors = $this->customer->validate();
            if (!empty($customerErrors)) {
                $errors = array_merge($errors, array_map(
                    fn($err) => "Customer: $err",
                    $customerErrors
                ));
            }
        }

        // Payments validation
        if (empty($this->payments)) {
            $errors[] = 'Order must have at least one payment';
        }

        foreach ($this->payments as $index => $payment) {
            $paymentErrors = $payment->validate();
            if (!empty($paymentErrors)) {
                $errors = array_merge($errors, array_map(
                    fn($err) => "Payment $index: $err",
                    $paymentErrors
                ));
            }
        }

        // Code validation (max 52 characters)
        if ($this->code !== null && strlen($this->code) > 52) {
            $errors[] = 'Order code must not exceed 52 characters';
        }

        return $errors;
    }

    /**
     * Validate antifraud requirements
     *
     * IMPORTANT: For antifraud analysis, the following fields are MANDATORY:
     * - customer: name, email, phones, document, type
     * - items
     * - address (customer.address OR billing_address in payment)
     */
    public function validateAntifraud(): array
    {
        $errors = [];

        // Customer validation for antifraud
        if ($this->customer instanceof CustomerDTO) {
            if (empty($this->customer->name)) {
                $errors[] = 'Antifraud: Customer name is required';
            }
            if (empty($this->customer->email)) {
                $errors[] = 'Antifraud: Customer email is required';
            }
            if (empty($this->customer->document)) {
                $errors[] = 'Antifraud: Customer document is required';
            }
            if (empty($this->customer->type)) {
                $errors[] = 'Antifraud: Customer type is required';
            }
            if (empty($this->customer->phones)) {
                $errors[] = 'Antifraud: Customer phones is required';
            }
        }

        // Items validation
        if (empty($this->items)) {
            $errors[] = 'Antifraud: At least one item is required';
        }

        // Address validation (at least one address is required)
        $hasAddress = false;

        // Check customer address
        if ($this->customer instanceof CustomerDTO && $this->customer->address !== null) {
            $hasAddress = true;
        }

        // Check billing address in payments
        if (!$hasAddress) {
            foreach ($this->payments as $payment) {
                if ($payment->creditCard !== null && $payment->creditCard->card instanceof \Kaninstein\LaravelPagarme\DTOs\CreditCardDTO) {
                    if ($payment->creditCard->card->billingAddress !== null) {
                        $hasAddress = true;
                        break;
                    }
                }
                if ($payment->boleto !== null) {
                    // Boleto também pode ter billing_address
                    $hasAddress = true;
                    break;
                }
            }
        }

        if (!$hasAddress) {
            $errors[] = 'Antifraud: At least one address is required (customer.address, credit_card.billing_address, or boleto.billing_address)';
        }

        return $errors;
    }

    /**
     * Check if order meets antifraud requirements
     */
    public function isAntifraudReady(): bool
    {
        return empty($this->validateAntifraud());
    }

    /**
     * Check if order is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
