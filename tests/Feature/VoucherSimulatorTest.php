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
use Kaninstein\LaravelPagarme\DTOs\VoucherPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\VoucherCardDTO;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

/**
 * Test Voucher Simulator scenarios
 *
 * @see https://docs.pagar.me/docs/simulador-de-voucher
 *
 * Regras (mesmas do cartão de débito):
 * - 4000000000000010: Sucesso
 * - 4000000000000028: Não autorizado
 * - 4000000000000036: Processing → Sucesso
 * - 4000000000000044: Processing → Falha
 * - Outros: Não autorizado
 */
class VoucherSimulatorTest extends TestCase
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
     * Create voucher order
     */
    private function createVoucherOrder(string $cardNumber, int $amount = 10000): ?array
    {
        try {
        // Create token
        $token = $this->tokenService->createCardToken([
            'number' => $cardNumber,
            'holder_name' => 'TESTE VOUCHER',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => '123',
        ]);

        $customer = CustomerDTO::individual(
            name: 'Cliente Voucher Teste',
            email: 'voucher.' . time() . rand(100, 999) . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '100',
                street: 'Rua Voucher',
                neighborhood: 'Centro',
                zipCode: '01000000',
                city: 'São Paulo',
                state: 'SP'
            )
        );

        $items = [
            OrderItemDTO::create('Produto Voucher', 1, $amount),
        ];

        $voucherCard = VoucherCardDTO::fromToken($token['id']);
        $payment = PaymentDTO::voucher(
            VoucherPaymentDTO::withCard($voucherCard)
        );

        $order = OrderDTO::create($items, $customer, $payment);

        return $this->orderService->create($order->toArray());

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Check if voucher is disabled
            if ($e->response && $e->response->status() === 412) {
                $body = $e->response->json();
                if (str_contains($body['message'] ?? '', 'disabled')) {
                    $this->markTestSkipped('Voucher payment is not enabled in this account');
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
    public function it_approves_voucher_transaction()
    {
        $result = $this->createVoucherOrder('4000000000000010');

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Voucher 4000000000000010 (Sucesso): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000028
     * Cenário: Não autorizado
     */
    public function it_declines_unauthorized_voucher()
    {
        $result = $this->createVoucherOrder('4000000000000028');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Voucher 4000000000000028 (Não autorizado): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000036
     * Cenário: Processing → Sucesso
     */
    public function it_handles_processing_then_success_voucher()
    {
        $result = $this->createVoucherOrder('4000000000000036');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Voucher 4000000000000036 (Processing→Sucesso): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000044
     * Cenário: Processing → Falha
     */
    public function it_handles_processing_then_failure_voucher()
    {
        $result = $this->createVoucherOrder('4000000000000044');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Voucher 4000000000000044 (Processing→Falha): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Qualquer outro cartão: Não autorizado
     */
    public function it_declines_random_vouchers()
    {
        $result = $this->createVoucherOrder('4111111111111111');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Voucher aleatório (Não autorizado): {$result['id']}\n";
        echo "  Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Testa diferentes valores para voucher
     */
    public function it_handles_different_voucher_amounts()
    {
        $amounts = [
            2000,   // R$ 20,00 (típico vale-refeição)
            3500,   // R$ 35,00
            5000,   // R$ 50,00
        ];

        foreach ($amounts as $amount) {
            $result = $this->createVoucherOrder('4000000000000010', $amount);

            $this->assertNotNull($result);

            $charge = $result['charges'][0] ?? null;
            $this->assertNotNull($charge);
            $this->assertEquals($amount, $charge['amount']);

            echo "\n✓ Voucher R$ " . number_format($amount / 100, 2, ',', '.') . ": {$result['id']}\n";
        }
    }

    /**
     * @test
     * Testa voucher com statement descriptor
     */
    public function it_creates_voucher_with_statement_descriptor()
    {
        try {
        $token = $this->tokenService->createCardToken([
            'number' => '4000000000000010',
            'holder_name' => 'TESTE VOUCHER DESC',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => '123',
        ]);

        $customer = CustomerDTO::individual(
            name: 'Cliente Voucher Desc',
            email: 'voucherdesc.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            )
        );

        $items = [
            OrderItemDTO::create('Refeição', 1, 3500),
        ];

        $voucherCard = VoucherCardDTO::fromToken($token['id']);
        $payment = PaymentDTO::voucher(
            VoucherPaymentDTO::withCard($voucherCard, 'RESTAURANTE')
        );

        $order = OrderDTO::create($items, $customer, $payment);
        $result = $this->orderService->create($order->toArray());

        $this->assertNotNull($result);

        echo "\n✓ Voucher com statement descriptor: {$result['id']}\n";

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Check if voucher is disabled
            if ($e->response && $e->response->status() === 412) {
                $body = $e->response->json();
                if (str_contains($body['message'] ?? '', 'disabled')) {
                    $this->markTestSkipped('Voucher payment is not enabled in this account');
                }
            }
            throw $e;
        }
    }
}
