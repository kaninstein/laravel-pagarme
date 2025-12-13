<?php

/**
 * Exemplo completo de criação de pedido com Pagarme
 *
 * Este exemplo demonstra como criar um pedido completo com:
 * - Cliente novo
 * - Itens do pedido
 * - Pagamento com cartão de crédito
 * - Metadata personalizado
 */

use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderItemDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\CreditCardDTO;
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\LaravelPagarme\Exceptions\ValidationException;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;

try {
    // 1. Criar o cliente
    $customerDTO = new CustomerDTO(
        name: 'João Silva',
        email: 'joao.silva@example.com',
        type: 'individual',
        document: '12345678900',
        documentType: 'CPF',
        phones: [
            'mobile_phone' => [
                'country_code' => '55',
                'area_code' => '11',
                'number' => '987654321'
            ]
        ],
        address: [
            'line_1' => 'Rua Exemplo, 123',
            'line_2' => 'Apto 45',
            'zip_code' => '01234567',
            'city' => 'São Paulo',
            'state' => 'SP',
            'country' => 'BR'
        ],
        metadata: [
            'customer_source' => 'website',
            'vip_customer' => 'true'
        ]
    );

    $customer = Pagarme::customers()->create($customerDTO->toArray());

    echo "Cliente criado: {$customer['id']}\n";

    // 2. Criar itens do pedido
    $items = [
        new OrderItemDTO(
            amount: 5000, // R$ 50,00 em centavos
            description: 'Produto Premium',
            quantity: 2,
            code: 'PROD-001',
            category: 'electronics'
        ),
        new OrderItemDTO(
            amount: 3000, // R$ 30,00
            description: 'Produto Standard',
            quantity: 1,
            code: 'PROD-002'
        ),
    ];

    // 3. Configurar pagamento com cartão de crédito
    $creditCard = new CreditCardDTO(
        number: '4111111111111111',
        holderName: 'JOAO SILVA',
        holderDocument: '12345678900',
        expMonth: 12,
        expYear: 2025,
        cvv: '123',
        billingAddress: [
            'line_1' => 'Rua Exemplo, 123',
            'zip_code' => '01234567',
            'city' => 'São Paulo',
            'state' => 'SP',
            'country' => 'BR'
        ]
    );

    $payment = PaymentDTO::creditCard(
        creditCard: $creditCard,
        installments: 3, // 3x sem juros
        statementDescriptor: 'MINHA LOJA'
    );

    // 4. Criar o pedido
    $orderDTO = new OrderDTO(
        items: $items,
        customer: $customer['id'], // Usar ID do cliente criado
        payments: [$payment],
        code: 'ORDER-' . time(),
        ip: request()->ip(),
        metadata: [
            'campaign_id' => 'SUMMER2025',
            'utm_source' => 'google',
            'utm_medium' => 'cpc'
        ]
    );

    $order = Pagarme::orders()->create($orderDTO->toArray());

    echo "Pedido criado: {$order['id']}\n";
    echo "Status: {$order['status']}\n";
    echo "Valor total: R$ " . number_format($order['amount'] / 100, 2, ',', '.') . "\n";

    // 5. Verificar cobranças do pedido
    $charges = Pagarme::orders()->charges($order['id']);

    echo "\nCobranças:\n";
    foreach ($charges['data'] as $charge) {
        echo "- Cobrança {$charge['id']}: {$charge['status']}\n";
    }

} catch (ValidationException $e) {
    echo "Erro de validação:\n";
    echo "Mensagem: {$e->getMessage()}\n";
    echo "Erros:\n";
    print_r($e->getErrors());

} catch (PagarmeException $e) {
    echo "Erro na API Pagarme:\n";
    echo "Mensagem: {$e->getMessage()}\n";
    echo "Código: {$e->getCode()}\n";
}
