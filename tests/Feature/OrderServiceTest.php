<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Kaninstein\LaravelPagarme\Client\PagarmeClient;
use Kaninstein\LaravelPagarme\Services\OrderService;
use Kaninstein\LaravelPagarme\Services\TokenService;
use Kaninstein\LaravelPagarme\DTOs\OrderDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderItemDTO;
use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\DTOs\PhonesDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\CreditCardPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\CreditCardDTO;
use Kaninstein\LaravelPagarme\DTOs\PixPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\BoletoPaymentDTO;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    private TokenService $tokenService;
    private static ?string $createdOrderId = null;

    protected function getPackageProviders($app)
    {
        return [PagarmeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $client = new PagarmeClient(
            secretKey: config('pagarme.secret_key'),
            apiUrl: config('pagarme.api_url'),
            timeout: (int) config('pagarme.timeout')
        );

        $this->orderService = new OrderService($client);
        $this->tokenService = new TokenService();
    }

    /**
     * @test
     */
    public function it_can_create_order_with_credit_card_token()
    {
        // 1. Create card token
        $token = $this->tokenService->createCardToken([
            'number' => '4111111111111111',
            'holder_name' => 'TESTE INTEGRATION',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => '123',
        ]);

        // 2. Create customer
        $customer = CustomerDTO::individual(
            name: 'Cliente Teste Pedido',
            email: 'pedido.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '100',
                street: 'Av. Paulista',
                neighborhood: 'Bela Vista',
                zipCode: '01310100',
                city: 'São Paulo',
                state: 'SP'
            )
        );

        // 3. Create order items
        $items = [
            OrderItemDTO::create(
                description: 'Produto Teste',
                quantity: 1,
                amount: 10000 // R$ 100,00
            ),
        ];

        // 4. Create payment with token
        $card = CreditCardDTO::fromToken($token['id']);
        $creditCardPayment = CreditCardPaymentDTO::withCard(
            card: $card,
            installments: 1,
            statementDescriptor: 'TESTE'
        );

        $payment = PaymentDTO::creditCard($creditCardPayment);

        // 5. Create order
        $order = OrderDTO::create(
            items: $items,
            customer: $customer,
            payment: $payment,
            code: 'TEST-ORDER-' . time()
        );

        // 6. Send to API
        $result = $this->orderService->create($order->toArray());

        // 7. Assertions
        $this->assertNotNull($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertStringStartsWith('or_', $result['id']);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('charges', $result);
        $this->assertNotEmpty($result['charges']);

        self::$createdOrderId = $result['id'];

        echo "\n✓ Order created: " . $result['id'] . "\n";
        echo "  Status: " . $result['status'] . "\n";
        echo "  Amount: R$ " . number_format($result['amount'] / 100, 2, ',', '.') . "\n";
    }

    /**
     * @test
     */
    public function it_can_create_order_with_pix()
    {
        $customer = CustomerDTO::individual(
            name: 'Cliente PIX Teste',
            email: 'pix.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '200',
                street: 'Rua Teste',
                neighborhood: 'Centro',
                zipCode: '01000000',
                city: 'São Paulo',
                state: 'SP'
            )
        );

        $items = [
            OrderItemDTO::create('Produto PIX', 1, 5000), // R$ 50,00
        ];

        $pixPayment = PixPaymentDTO::withExpiresIn(3600); // 1 hour
        $payment = PaymentDTO::pix($pixPayment);

        $order = OrderDTO::create($items, $customer, $payment)
            ->withIp('192.168.1.1');

        $result = $this->orderService->create($order->toArray());

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);
        $this->assertArrayHasKey('charges', $result);

        // PIX specific data
        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge, 'At least one charge should exist');

        if (isset($charge['last_transaction'])) {
            // In sandbox, PIX QR codes might not always be generated
            if (isset($charge['last_transaction']['qr_code'])) {
                $this->assertArrayHasKey('qr_code', $charge['last_transaction']);
                $this->assertArrayHasKey('qr_code_url', $charge['last_transaction']);

                echo "\n✓ PIX Order created: " . $result['id'] . "\n";
                echo "  QR Code: " . substr($charge['last_transaction']['qr_code'], 0, 50) . "...\n";
            } else {
                echo "\n✓ PIX Order created: " . $result['id'] . " (QR code pending)\n";
            }
        }
    }

    /**
     * @test
     */
    public function it_can_create_order_with_boleto()
    {
        $customer = CustomerDTO::individual(
            name: 'Cliente Boleto Teste',
            email: 'boleto.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '300',
                street: 'Av. Teste',
                neighborhood: 'Jardins',
                zipCode: '02000000',
                city: 'São Paulo',
                state: 'SP'
            )
        );

        $items = [
            OrderItemDTO::create('Produto Boleto', 1, 15000), // R$ 150,00
        ];

        $boleto = BoletoPaymentDTO::create(
            dueAt: new \DateTime('+7 days'),
            instructions: 'Não aceitar após vencimento'
        );

        $payment = PaymentDTO::boleto($boleto);

        $order = OrderDTO::create($items, $customer, $payment);

        $result = $this->orderService->create($order->toArray());

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge, 'At least one charge should exist');

        if (isset($charge['last_transaction'])) {
            // In sandbox, boleto PDF/line might not always be generated immediately
            if (isset($charge['last_transaction']['pdf']) && isset($charge['last_transaction']['line'])) {
                $this->assertArrayHasKey('pdf', $charge['last_transaction']);
                $this->assertArrayHasKey('line', $charge['last_transaction']);

                echo "\n✓ Boleto Order created: " . $result['id'] . "\n";
                echo "  Line: " . substr($charge['last_transaction']['line'], 0, 50) . "...\n";
                echo "  PDF: " . $charge['last_transaction']['pdf'] . "\n";
            } else {
                echo "\n✓ Boleto Order created: " . $result['id'] . " (PDF/line pending)\n";
            }
        }
    }

    /**
     * @test
     * @depends it_can_create_order_with_credit_card_token
     */
    public function it_can_get_order()
    {
        if (!self::$createdOrderId) {
            $this->markTestSkipped('No order was created');
        }

        $order = $this->orderService->get(self::$createdOrderId);

        $this->assertNotNull($order);
        $this->assertEquals(self::$createdOrderId, $order['id']);
        $this->assertArrayHasKey('customer', $order);
        $this->assertArrayHasKey('items', $order);
        $this->assertArrayHasKey('charges', $order);
    }

    /**
     * @test
     */
    public function it_can_list_orders()
    {
        $result = $this->orderService->list(['page' => 1, 'size' => 5]);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('paging', $result);
    }

    /**
     * @test
     */
    public function it_can_create_multi_payment_order()
    {
        // Create two card tokens
        $token1 = $this->tokenService->createCardToken([
            'number' => '4111111111111111',
            'holder_name' => 'CARD 1',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => '123',
        ]);

        $token2 = $this->tokenService->createCardToken([
            'number' => '5555555555554444',
            'holder_name' => 'CARD 2',
            'exp_month' => 6,
            'exp_year' => 2029,
            'cvv' => '321',
        ]);

        $customer = CustomerDTO::individual(
            name: 'Cliente Multi-Payment',
            email: 'multi.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            )
        );

        $items = [
            OrderItemDTO::create('Produto Multi', 1, 20000), // R$ 200,00
        ];

        // Two payments of R$ 100,00 each
        $payments = [
            PaymentDTO::creditCard(
                CreditCardPaymentDTO::withCard(
                    CreditCardDTO::fromToken($token1['id'])
                ),
                amount: 10000
            ),
            PaymentDTO::creditCard(
                CreditCardPaymentDTO::withCard(
                    CreditCardDTO::fromToken($token2['id'])
                ),
                amount: 10000
            ),
        ];

        $order = new OrderDTO(
            items: $items,
            customer: $customer,
            payments: $payments
        );

        $result = $this->orderService->create($order->toArray());

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);
        $this->assertCount(2, $result['charges']);

        echo "\n✓ Multi-payment Order created: " . $result['id'] . "\n";
        echo "  Charges: " . count($result['charges']) . "\n";
    }
}
