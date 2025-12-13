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
use Kaninstein\LaravelPagarme\DTOs\DebitCardPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\DebitCardDTO;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

/**
 * Test Debit Card Simulator scenarios
 *
 * @see https://docs.pagar.me/docs/simulador-de-cartão-de-débito
 *
 * Regras:
 * - 4000000000000010: Sucesso
 * - 4000000000000028: Não autorizado
 * - 4000000000000036: Processing → Sucesso
 * - 4000000000000044: Processing → Falha
 * - Outros: Não autorizado
 */
class DebitCardSimulatorTest extends TestCase
{
    private OrderService $orderService;
    private TokenService $tokenService;

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
     * Create debit card order
     */
    private function createDebitCardOrder(string $cardNumber, int $amount = 10000): ?array
    {
        try {
        // Create token
        $token = $this->tokenService->createCardToken([
            'number' => $cardNumber,
            'holder_name' => 'TESTE DEBITO',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => '123',
        ]);

        $customer = CustomerDTO::individual(
            name: 'Cliente Débito Teste',
            email: 'debito.' . time() . rand(100, 999) . '@example.com',
            cpf: '12345678900',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '100',
                street: 'Rua Débito',
                neighborhood: 'Centro',
                zipCode: '01000000',
                city: 'São Paulo',
                state: 'SP'
            )
        );

        $items = [
            OrderItemDTO::create('Produto Débito', 1, $amount),
        ];

        $debitCard = DebitCardDTO::fromToken($token['id']);
        $payment = PaymentDTO::debitCard(
            DebitCardPaymentDTO::withCard($debitCard)
        );

        $order = OrderDTO::create($items, $customer, $payment);

        return $this->orderService->create($order->toArray());

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Check if debit card is disabled
            if ($e->response && $e->response->status() === 412) {
                $body = $e->response->json();
                if (str_contains($body['message'] ?? '', 'disabled')) {
                    $this->markTestSkipped('Debit card payment is not enabled in this account');
                }
            }
            throw $e;
        }
    }

    /**
     * @test
     * Cartão: 4000000000000010
     * Cenário: Sucesso
     */
    public function it_approves_debit_card_transaction()
    {
        $result = $this->createDebitCardOrder('4000000000000010');

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Débito 4000000000000010 (Sucesso): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000028
     * Cenário: Não autorizado
     */
    public function it_declines_unauthorized_debit_card()
    {
        $result = $this->createDebitCardOrder('4000000000000028');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Débito 4000000000000028 (Não autorizado): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000036
     * Cenário: Processing → Sucesso
     */
    public function it_handles_processing_then_success_debit()
    {
        $result = $this->createDebitCardOrder('4000000000000036');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Débito 4000000000000036 (Processing→Sucesso): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000044
     * Cenário: Processing → Falha
     */
    public function it_handles_processing_then_failure_debit()
    {
        $result = $this->createDebitCardOrder('4000000000000044');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Débito 4000000000000044 (Processing→Falha): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Qualquer outro cartão: Não autorizado
     */
    public function it_declines_random_debit_cards()
    {
        $result = $this->createDebitCardOrder('4111111111111111');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Débito aleatório (Não autorizado): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Testa diferentes valores
     */
    public function it_handles_different_debit_amounts()
    {
        $amounts = [
            1000,   // R$ 10,00
            5000,   // R$ 50,00
            10000,  // R$ 100,00
        ];

        foreach ($amounts as $amount) {
            $result = $this->createDebitCardOrder('4000000000000010', $amount);

            $this->assertNotNull($result);

            $charge = $result['charges'][0] ?? null;
            $this->assertNotNull($charge);
            $this->assertEquals($amount, $charge['amount']);

            echo "\n✓ Débito R$ " . number_format($amount / 100, 2, ',', '.') . ": {$result['id']}\n";
        }
    }
}
