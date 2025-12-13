<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Kaninstein\LaravelPagarme\Client\PagarmeClient;
use Kaninstein\LaravelPagarme\Services\OrderService;
use Kaninstein\LaravelPagarme\DTOs\OrderDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderItemDTO;
use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\DTOs\PhonesDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\BoletoPaymentDTO;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

/**
 * Test Boleto Simulator scenarios
 *
 * @see https://docs.pagar.me/docs/simulador-de-boleto-bancário
 *
 * Simulador Gateway: Os CEPs do endereço do comprador determinam o cenário
 */
class BoletoSimulatorTest extends TestCase
{
    private OrderService $orderService;

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
    }

    /**
     * Create order with boleto using specific zipcode
     */
    private function createBoletoOrder(string $zipCode, int $amount = 15000): array
    {
        $customer = CustomerDTO::individual(
            name: 'Cliente Boleto Teste',
            email: 'boleto.' . time() . '@example.com',
            cpf: '12345678900',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '100',
                street: 'Rua Teste Boleto',
                neighborhood: 'Centro',
                zipCode: $zipCode, // CEP determina o cenário
                city: 'São Paulo',
                state: 'SP'
            )
        );

        $items = [
            OrderItemDTO::create('Produto Boleto', 1, $amount),
        ];

        $boleto = BoletoPaymentDTO::create(
            dueAt: new \DateTime('+7 days'),
            instructions: 'Não aceitar após vencimento'
        );

        $payment = PaymentDTO::boleto($boleto);

        $order = OrderDTO::create($items, $customer, $payment);

        return $this->orderService->create($order->toArray());
    }

    /**
     * @test
     * CEP: 01046010
     * Cenário: Boleto será conciliado com pagamento a menor
     */
    public function it_simulates_boleto_with_underpayment()
    {
        $result = $this->createBoletoOrder('01046010', 15000); // R$ 150,00

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Boleto CEP 01046010 (Pagamento a menor): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";

        if (isset($charge['last_transaction']['pdf'])) {
            echo "  PDF: {$charge['last_transaction']['pdf']}\n";
        }
    }

    /**
     * @test
     * CEP: 57400000
     * Cenário: Boleto será conciliado com pagamento a maior
     */
    public function it_simulates_boleto_with_overpayment()
    {
        $result = $this->createBoletoOrder('57400000', 15000); // R$ 150,00

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Boleto CEP 57400000 (Pagamento a maior): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";

        if (isset($charge['last_transaction']['line'])) {
            echo "  Linha digitável: " . substr($charge['last_transaction']['line'], 0, 20) . "...\n";
        }
    }

    /**
     * @test
     * CEP: 70070300
     * Cenário: Boleto não será conciliado
     */
    public function it_simulates_boleto_not_reconciled()
    {
        $result = $this->createBoletoOrder('70070300', 15000); // R$ 150,00

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Boleto CEP 70070300 (Não conciliado): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * CEP: Qualquer outro
     * Cenário: Boleto será conciliado com pagamento total
     */
    public function it_simulates_boleto_with_full_payment()
    {
        $result = $this->createBoletoOrder('01000000', 15000); // R$ 150,00

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Boleto CEP genérico (Pagamento total): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
        echo "  Valor: R$ " . number_format($charge['amount'] / 100, 2, ',', '.') . "\n";
    }

    /**
     * @test
     * Testa diferentes valores de boleto
     */
    public function it_handles_different_boleto_amounts()
    {
        // Valores mínimos e máximos comuns
        $amounts = [
            500,      // R$ 5,00
            10000,    // R$ 100,00
            100000,   // R$ 1.000,00
        ];

        foreach ($amounts as $amount) {
            $result = $this->createBoletoOrder('01000000', $amount);

            $this->assertNotNull($result);

            $charge = $result['charges'][0] ?? null;
            $this->assertNotNull($charge);
            $this->assertEquals($amount, $charge['amount']);

            echo "\n✓ Boleto R$ " . number_format($amount / 100, 2, ',', '.') . ": {$result['id']}\n";
        }
    }
}
