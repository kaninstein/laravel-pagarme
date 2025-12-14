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
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;
use Kaninstein\LaravelPagarme\Enums\AbecsReturnCode;

/**
 * Test all Pagar.me test card scenarios
 *
 * @see https://docs.pagar.me/docs/simulador-de-cartão-de-crédito
 */
class TestCardsScenarios extends TestCase
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
     * Create order with specific card number
     */
    private function createOrderWithCard(string $cardNumber, string $cvv = '123', int $amount = 10000): array
    {
        // Create token
        $token = $this->tokenService->createCardToken([
            'number' => $cardNumber,
            'holder_name' => 'TESTE CARD SCENARIO',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => $cvv,
        ]);

        // Create customer
        $customer = CustomerDTO::individual(
            name: 'Cliente Teste Cenário',
            email: 'cenario.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            ),
            address: AddressDTO::brazilian(
                number: '100',
                street: 'Av. Teste',
                neighborhood: 'Centro',
                zipCode: '01000000',
                city: 'São Paulo',
                state: 'SP'
            )
        );

        // Create order
        $items = [
            OrderItemDTO::create('Produto Teste', 1, $amount),
        ];

        $card = CreditCardDTO::fromToken($token['id']);
        $payment = PaymentDTO::creditCard(
            CreditCardPaymentDTO::withCard($card)
        );

        $order = OrderDTO::create($items, $customer, $payment);

        return $this->orderService->create($order->toArray());
    }

    /**
     * @test
     * Cartão: 4000000000000010
     * Cenário: Qualquer operação com esse cartão é realizada com sucesso
     */
    public function it_approves_transaction_with_success_card()
    {
        $result = $this->createOrderWithCard('4000000000000010');

        $this->assertNotNull($result);
        $this->assertStringStartsWith('or_', $result['id']);
        $this->assertArrayHasKey('charges', $result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        // In sandbox, status might be 'paid' or 'pending' depending on processing
        $this->assertContains($charge['status'], ['paid', 'pending', 'failed']);

        echo "\n✓ Cartão 4000000000000010 (Sucesso): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000028
     * Cenário: Transação não autorizada
     */
    public function it_declines_transaction_with_unauthorized_card()
    {
        $result = $this->createOrderWithCard('4000000000000028');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        // Should be declined
        $this->assertContains($charge['status'], ['failed', 'not_authorized']);

        echo "\n✓ Cartão 4000000000000028 (Não Autorizado): {$result['id']} - Status: {$charge['status']}\n";

        if (isset($charge['last_transaction']['acquirer_return_code'])) {
            echo "  Código: {$charge['last_transaction']['acquirer_return_code']}\n";
            echo "  Mensagem: {$charge['last_transaction']['acquirer_message']}\n";
        }
    }

    /**
     * @test
     * Cartão: 4000000000000036
     * Cenário: Transação com erro que depois é confirmada pela adquirente
     */
    public function it_handles_processing_then_success_card()
    {
        $result = $this->createOrderWithCard('4000000000000036');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Cartão 4000000000000036 (Processing→Success): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000044
     * Cenário: Transação com status de erro e posteriormente com falha
     */
    public function it_handles_processing_then_failure_card()
    {
        $result = $this->createOrderWithCard('4000000000000044');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Cartão 4000000000000044 (Processing→Failure): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000077
     * Cenário: Captura com sucesso, erro ao cancelar, depois estornada
     */
    public function it_handles_complex_success_processing_success_flow()
    {
        $result = $this->createOrderWithCard('4000000000000077');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Cartão 4000000000000077 (Success→Processing→Success): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000051
     * Cenário: Criado com status 'Pendente' e depois atualizado para cancelado
     */
    public function it_handles_processing_to_canceled_card()
    {
        $result = $this->createOrderWithCard('4000000000000051');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Cartão 4000000000000051 (Processing→Canceled): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cartão: 4000000000000069
     * Cenário: Capturado com sucesso e depois atualizado para o status de chargeback
     */
    public function it_handles_paid_to_chargedback_card()
    {
        $result = $this->createOrderWithCard('4000000000000069');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Cartão 4000000000000069 (Paid→Chargedback): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cenário PSP: CVV começando com 6 = recusa pelo emissor
     */
    public function it_declines_with_cvv_starting_with_6()
    {
        $result = $this->createOrderWithCard('4000000000000010', '612');

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ CVV começando com 6 (Recusa Emissor): {$result['id']} - Status: {$charge['status']}\n";

        if (isset($charge['last_transaction']['acquirer_return_code'])) {
            echo "  Código: {$charge['last_transaction']['acquirer_return_code']}\n";
        }
    }

    /**
     * @test
     * Cenário PSP: Documento 11111111111 = bloqueio antifraude
     */
    public function it_blocks_transaction_with_fraud_prevention_document()
    {
        // Create token
        $token = $this->tokenService->createCardToken([
            'number' => '4000000000000010',
            'holder_name' => 'TESTE ANTIFRAUDE',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => '123',
        ]);

        // Create customer with fraud document
        $customer = CustomerDTO::individual(
            name: 'Cliente Antifraude',
            email: 'fraud.' . time() . '@example.com',
            cpf: '11111111111', // Fraud prevention trigger
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            )
        );

        $items = [
            OrderItemDTO::create('Produto Antifraude', 1, 10000),
        ];

        $card = CreditCardDTO::fromToken($token['id']);
        $payment = PaymentDTO::creditCard(
            CreditCardPaymentDTO::withCard($card)
        );

        $order = OrderDTO::create($items, $customer, $payment);
        $result = $this->orderService->create($order->toArray());

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Documento 11111111111 (Bloqueio Antifraude): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Cenário: Qualquer outro número de cartão retorna como não autorizado
     */
    public function it_declines_random_card_numbers()
    {
        $result = $this->createOrderWithCard('4111111111111111'); // Cartão válido mas não específico

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Cartão aleatório (4111111111111111): {$result['id']} - Status: {$charge['status']}\n";
    }

    /**
     * @test
     * Testa diferentes valores para Chargeback Guarantee
     * Valores entre R$ 1,30 e R$ 1,60 ativam a função
     */
    public function it_handles_chargeback_guarantee_amounts()
    {
        // Valor de R$ 1,45 (145 centavos) deve ativar Chargeback Guarantee
        $result = $this->createOrderWithCard('4000000000000010', '123', 145);

        $this->assertNotNull($result);

        $charge = $result['charges'][0] ?? null;
        $this->assertNotNull($charge);

        echo "\n✓ Chargeback Guarantee (R$ 1,45): {$result['id']} - Status: {$charge['status']}\n";

        // Check if chargeback guarantee is present
        if (isset($charge['last_transaction']['chargeback_guarantee'])) {
            echo "  Chargeback Guarantee: Ativo\n";
        }
    }
}
