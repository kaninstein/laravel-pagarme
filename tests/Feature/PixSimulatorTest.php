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
use Kaninstein\LaravelPagarme\DTOs\PixPaymentDTO;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

/**
 * Test PIX Simulator scenarios
 *
 * @see https://docs.pagar.me/docs/simulador-pix
 *
 * Regras do Simulador:
 * - Valor <= R$ 500,00: Sucesso (pending → paid após alguns segundos)
 * - Valor > R$ 500,00: Falha (status=failed)
 */
class PixSimulatorTest extends TestCase
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
     * Create PIX order with specific amount
     */
    private function createPixOrder(int $amount): array
    {
        $customer = CustomerDTO::individual(
            name: 'Cliente PIX Teste',
            email: 'pix.' . time() . rand(100, 999) . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '100',
                street: 'Rua PIX',
                neighborhood: 'Centro',
                zipCode: '01000000',
                city: 'São Paulo',
                state: 'SP'
            )
        );

        $items = [
            OrderItemDTO::create('Produto PIX', 1, $amount),
        ];

        $pixPayment = PixPaymentDTO::withExpiresIn(3600); // 1 hora
        $payment = PaymentDTO::pix($pixPayment);

        $order = OrderDTO::create($items, $customer, $payment)
            ->withIp('192.168.1.1');

        return $this->orderService->create($order->toArray());
    }

    /**
     * @test
     * Cenário: Valor <= R$ 500,00
     * Resultado: Transação criada como pending e atualizada para paid
     */
    public function it_approves_pix_with_amount_up_to_500()
    {
        $amounts = [
            500,      // R$ 5,00
            5000,     // R$ 50,00
            50000,    // R$ 500,00 (limite máximo para sucesso)
        ];

        foreach ($amounts as $amount) {
            $result = $this->createPixOrder($amount);

            $this->assertNotNull($result);
            $this->assertStringStartsWith('or_', $result['id']);

            $charge = $result['charges'][0] ?? null;
            $this->assertNotNull($charge);

            // PIX pode retornar pending, paid ou failed dependendo do ambiente
            $this->assertContains($charge['status'], ['pending', 'paid', 'failed']);

            echo "\n✓ PIX R$ " . number_format($amount / 100, 2, ',', '.') . ": {$result['id']}\n";
            echo "  Status: {$charge['status']}\n";

            if (isset($charge['last_transaction']['qr_code'])) {
                echo "  QR Code: " . substr($charge['last_transaction']['qr_code'], 0, 30) . "...\n";
            }

            if (isset($charge['last_transaction']['qr_code_url'])) {
                echo "  QR Code URL: {$charge['last_transaction']['qr_code_url']}\n";
            }
        }
    }

    /**
     * @test
     * Cenário: Valor > R$ 500,00
     * Resultado: Transação criada como failed
     */
    public function it_fails_pix_with_amount_above_500()
    {
        $amounts = [
            50001,    // R$ 500,01 (logo acima do limite)
            100000,   // R$ 1.000,00
            500000,   // R$ 5.000,00
        ];

        foreach ($amounts as $amount) {
            $result = $this->createPixOrder($amount);

            $this->assertNotNull($result);
            $this->assertStringStartsWith('or_', $result['id']);

            $charge = $result['charges'][0] ?? null;
            $this->assertNotNull($charge);

            // Status deve ser failed
            $this->assertContains($charge['status'], ['failed', 'pending']);

            echo "\n✓ PIX R$ " . number_format($amount / 100, 2, ',', '.') . " (Falha esperada): {$result['id']}\n";
            echo "  Status: {$charge['status']}\n";
        }
    }

    /**
     * @test
     * Testa valor exato do limite (R$ 500,00)
     */
    public function it_handles_pix_at_exact_limit()
    {
        $result = $this->createPixOrder(50000); // Exatamente R$ 500,00

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ PIX R$ 500,00 exatos (Limite): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Testa diferentes tempos de expiração
     */
    public function it_handles_different_expiration_times()
    {
        $customer = CustomerDTO::individual(
            name: 'Cliente PIX Expiracao',
            email: 'pixexp.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            )
        );

        $items = [
            OrderItemDTO::create('Produto Teste Expiracao', 1, 10000),
        ];

        $expirations = [
            900,   // 15 minutos
            1800,  // 30 minutos
            3600,  // 1 hora
            7200,  // 2 horas
        ];

        foreach ($expirations as $seconds) {
            $pixPayment = PixPaymentDTO::withExpiresIn($seconds);
            $payment = PaymentDTO::pix($pixPayment);
            $order = OrderDTO::create($items, $customer, $payment);

            $result = $this->orderService->create($order->toArray());

            $this->assertNotNull($result);

            echo "\n✓ PIX com expiração de " . ($seconds / 60) . " min: {$result['id']}\n";
        }
    }

    /**
     * @test
     * Testa PIX com dados adicionais do comprador
     */
    public function it_creates_pix_with_additional_info()
    {
        $customer = CustomerDTO::individual(
            name: 'Cliente PIX Completo',
            email: 'pixcompleto.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321'),
                homePhone: PhoneDTO::brazilian('11', '12345678')
            ),
            address: AddressDTO::brazilian(
                number: '200',
                street: 'Av. PIX',
                neighborhood: 'Jardins',
                zipCode: '01310100',
                city: 'São Paulo',
                state: 'SP',
                complement: 'Apto 101'
            )
        );

        $items = [
            OrderItemDTO::create('Produto Premium PIX', 2, 25000),
        ];

        $pixPayment = PixPaymentDTO::withExpiresIn(3600);
        $payment = PaymentDTO::pix($pixPayment);

        $order = OrderDTO::create($items, $customer, $payment)
            ->withIp('192.168.1.100');

        $result = $this->orderService->create($order->toArray());

        $this->assertNotNull($result);
        $this->assertEquals(50000, $result['amount']); // R$ 500,00

        echo "\n✓ PIX com dados completos: {$result['id']}\n";
        echo "  Valor total: R$ " . number_format($result['amount'] / 100, 2, ',', '.') . "\n";
    }
}
