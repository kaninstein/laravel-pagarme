<?php

/**
 * Antifraud (Antifraude) Examples
 *
 * This file demonstrates how to create orders with antifraud analysis enabled
 *
 * IMPORTANT REQUIREMENTS FOR ANTIFRAUD:
 * - Customer: name, email, phones, document, type
 * - Items
 * - Address (customer.address OR billing_address in payment)
 * - IP address (recommended)
 * - Session ID (recommended)
 */

use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\LaravelPagarme\DTOs\OrderDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderItemDTO;
use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\DTOs\PhonesDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\CreditCardPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\CreditCardDTO;

/**
 * Example 1: Complete Order with Antifraud
 * All required fields for antifraud analysis
 */
function example1_completeOrderWithAntifraud()
{
    // 1. Customer with all required data
    $customer = CustomerDTO::individual(
        name: 'João Silva',
        email: 'joao.silva@example.com',
        cpf: '12345678900',
        phones: PhonesDTO::brazilian(
            mobilePhone: PhoneDTO::brazilian('11', '987654321'),
            homePhone: PhoneDTO::brazilian('11', '12345678')
        ),
        address: AddressDTO::brazilian(
            number: '100',
            street: 'Av. Paulista',
            neighborhood: 'Bela Vista',
            zipCode: '01310-100',
            city: 'São Paulo',
            state: 'SP',
            complement: 'Apto 101'
        )
    );

    // 2. Order items
    $items = [
        OrderItemDTO::create(
            description: 'Notebook Dell',
            quantity: 1,
            amount: 350000 // R$ 3.500,00
        ),
        OrderItemDTO::create(
            description: 'Mouse Logitech',
            quantity: 2,
            amount: 15000 // R$ 150,00
        ),
    ];

    // 3. Payment with billing address
    $card = new CreditCardDTO(
        number: '4111111111111111',
        holderName: 'João Silva',
        expMonth: 12,
        expYear: 2030,
        cvv: '123',
        billingAddress: AddressDTO::brazilian(
            number: '100',
            street: 'Av. Paulista',
            neighborhood: 'Bela Vista',
            zipCode: '01310-100',
            city: 'São Paulo',
            state: 'SP'
        )
    );

    $creditCardPayment = CreditCardPaymentDTO::withCard(
        card: $card,
        installments: 3,
        statementDescriptor: 'LOJA TECH'
    );

    $payment = PaymentDTO::creditCard($creditCardPayment);

    // 4. Create order with antifraud
    $order = OrderDTO::create(
        items: $items,
        customer: $customer,
        payment: $payment,
        code: 'ORDER-' . time()
    );

    // Enable antifraud (Gateway clients only)
    $order->withAntifraud(true)
        ->withIp('192.168.1.100') // Customer's IP address
        ->withSessionId('session_' . uniqid()); // Session identifier

    // 5. Validate antifraud requirements
    $antifraudErrors = $order->validateAntifraud();
    if (!empty($antifraudErrors)) {
        echo "Antifraud validation errors:\n";
        foreach ($antifraudErrors as $error) {
            echo "  - $error\n";
        }
        return;
    }

    echo "✓ Order is ready for antifraud analysis\n";

    // 6. Create order
    try {
        $result = Pagarme::orders()->create($order->toArray());
        echo "Order created: " . $result['id'] . "\n";
        echo "Antifraud status: " . ($result['antifraud'] ?? 'N/A') . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

/**
 * Example 2: Order with Shipping Information
 * Shipping data can improve antifraud analysis
 */
function example2_orderWithShipping()
{
    $customer = CustomerDTO::individual(
        name: 'Maria Santos',
        email: 'maria@example.com',
        cpf: '98765432100',
        phones: PhonesDTO::brazilian(
            mobilePhone: PhoneDTO::brazilian('21', '987654321')
        ),
        address: AddressDTO::brazilian(
            number: '200',
            street: 'Rua das Flores',
            neighborhood: 'Centro',
            zipCode: '20000-000',
            city: 'Rio de Janeiro',
            state: 'RJ'
        )
    );

    $items = [
        OrderItemDTO::create('Livro de PHP', 1, 5000),
    ];

    $payment = PaymentDTO::creditCard(
        CreditCardPaymentDTO::withCardId('card_xxxxx')
    );

    $order = OrderDTO::create($items, $customer, $payment)
        ->withAntifraud(true)
        ->withIp($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')
        ->withSessionId(session_id())
        ->withShipping([
            'amount' => 1500, // R$ 15,00
            'description' => 'Sedex',
            'recipient_name' => 'Maria Santos',
            'recipient_phone' => '21987654321',
            'address' => [
                'line_1' => '200, Rua das Flores, Centro',
                'line_2' => 'Casa',
                'zip_code' => '20000000',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'country' => 'BR',
            ],
        ]);

    echo "Order ready with shipping info\n";
}

/**
 * Example 3: Checking Antifraud Requirements
 * Validate before sending to API
 */
function example3_checkAntifraudRequirements()
{
    // Incomplete customer (missing phones)
    $customer = new CustomerDTO(
        name: 'Test User',
        email: 'test@example.com',
        document: '12345678900',
        type: 'individual'
        // Missing: phones and address
    );

    $items = [OrderItemDTO::create('Product', 1, 10000)];
    $payment = PaymentDTO::creditCard(
        CreditCardPaymentDTO::withCardId('card_xxxxx')
    );

    $order = OrderDTO::create($items, $customer, $payment)
        ->withAntifraud(true);

    // Check if ready for antifraud
    if (!$order->isAntifraudReady()) {
        echo "⚠️ Order is NOT ready for antifraud analysis:\n";
        foreach ($order->validateAntifraud() as $error) {
            echo "  - $error\n";
        }
        // Output:
        // - Antifraud: Customer phones is required
        // - Antifraud: At least one address is required
    } else {
        echo "✓ Order is ready for antifraud\n";
    }
}

/**
 * Example 4: PSP vs Gateway Antifraud
 */
function example4_pspVsGateway()
{
    $customer = CustomerDTO::individual(
        name: 'Carlos Mendes',
        email: 'carlos@example.com',
        cpf: '11111111111',
        phones: PhonesDTO::brazilian(
            mobilePhone: PhoneDTO::brazilian('11', '999999999')
        ),
        address: AddressDTO::brazilian('10', 'Rua A', 'Centro', '01000-000', 'São Paulo', 'SP')
    );

    $items = [OrderItemDTO::create('Item', 1, 10000)];
    $payment = PaymentDTO::creditCard(
        CreditCardPaymentDTO::withCardId('card_xxxxx')
    );

    // For PSP clients: Antifraud is always enabled
    $orderPSP = OrderDTO::create($items, $customer, $payment);
    echo "PSP: Antifraud always enabled\n";

    // For Gateway clients: Can enable/disable per order
    $orderGateway = OrderDTO::create($items, $customer, $payment)
        ->withAntifraud(true); // Enable antifraud
    echo "Gateway: Antifraud explicitly enabled\n";

    $orderGatewayDisabled = OrderDTO::create($items, $customer, $payment)
        ->withAntifraud(false); // Disable antifraud
    echo "Gateway: Antifraud explicitly disabled\n";
}

/**
 * Example 5: Device and Location Data
 * Additional data for better fraud detection
 */
function example5_deviceAndLocation()
{
    $customer = CustomerDTO::individual(
        name: 'Ana Costa',
        email: 'ana@example.com',
        cpf: '22222222222',
        phones: PhonesDTO::brazilian(
            mobilePhone: PhoneDTO::brazilian('11', '888888888')
        ),
        address: AddressDTO::brazilian('50', 'Av. Brasil', 'Jardins', '05000-000', 'São Paulo', 'SP')
    );

    $items = [OrderItemDTO::create('Smartphone', 1, 200000)];
    $payment = PaymentDTO::creditCard(
        CreditCardPaymentDTO::withCardId('card_xxxxx')
    );

    $order = OrderDTO::create($items, $customer, $payment)
        ->withAntifraud(true)
        ->withIp('200.150.100.50')
        ->withSessionId('sess_abc123xyz')
        ->withDevice([
            'platform' => 'Android',
        ])
        ->withLocation([
            'latitude' => '-23.550520',
            'longitude' => '-46.633308',
        ]);

    echo "Order with device and location data\n";
}

/**
 * Example 6: PIX Payment with Antifraud
 * PIX also requires antifraud data
 */
function example6_pixWithAntifraud()
{
    $customer = CustomerDTO::individual(
        name: 'Pedro Oliveira',
        email: 'pedro@example.com',
        cpf: '33333333333',
        phones: PhonesDTO::brazilian(
            mobilePhone: PhoneDTO::brazilian('11', '777777777')
        ),
        address: AddressDTO::brazilian('30', 'Rua C', 'Vila Nova', '03000-000', 'São Paulo', 'SP')
    );

    $items = [OrderItemDTO::create('Curso Online', 1, 19900)];

    $pixPayment = \Kaninstein\LaravelPagarme\DTOs\PixPaymentDTO::withExpiresIn(
        expiresIn: 3600, // 1 hour
        additionalInformation: [
            \Kaninstein\LaravelPagarme\DTOs\AdditionalInformationDTO::create('Curso', 'PHP Avançado'),
        ]
    );

    $payment = PaymentDTO::pix($pixPayment);

    $order = OrderDTO::create($items, $customer, $payment)
        ->withAntifraud(true)
        ->withIp('192.168.1.1')
        ->withSessionId('pix_session_123');

    if ($order->isAntifraudReady()) {
        echo "✓ PIX order ready for antifraud\n";
    }
}

/**
 * Example 7: Boleto with Antifraud
 */
function example7_boletoWithAntifraud()
{
    $customer = CustomerDTO::individual(
        name: 'Fernanda Lima',
        email: 'fernanda@example.com',
        cpf: '44444444444',
        phones: PhonesDTO::brazilian(
            mobilePhone: PhoneDTO::brazilian('11', '666666666')
        ),
        address: AddressDTO::brazilian('40', 'Rua D', 'Consolação', '04000-000', 'São Paulo', 'SP')
    );

    $items = [OrderItemDTO::create('Mensalidade', 1, 50000)];

    $boleto = \Kaninstein\LaravelPagarme\DTOs\BoletoPaymentDTO::create(
        dueAt: new DateTime('+7 days'),
        instructions: 'Não aceitar após o vencimento'
    );

    $payment = PaymentDTO::boleto($boleto);

    $order = OrderDTO::create($items, $customer, $payment)
        ->withAntifraud(true)
        ->withIp('10.0.0.1')
        ->withSessionId('boleto_sess_456');

    echo "Boleto order with antifraud\n";
}

// Run examples
echo "=== Example 1: Complete Order with Antifraud ===\n";
example1_completeOrderWithAntifraud();
echo "\n";

echo "=== Example 2: Order with Shipping ===\n";
example2_orderWithShipping();
echo "\n";

echo "=== Example 3: Check Requirements ===\n";
example3_checkAntifraudRequirements();
echo "\n";

echo "=== Example 4: PSP vs Gateway ===\n";
example4_pspVsGateway();
echo "\n";

echo "=== Example 5: Device and Location ===\n";
example5_deviceAndLocation();
echo "\n";

echo "=== Example 6: PIX with Antifraud ===\n";
example6_pixWithAntifraud();
echo "\n";

echo "=== Example 7: Boleto with Antifraud ===\n";
example7_boletoWithAntifraud();
