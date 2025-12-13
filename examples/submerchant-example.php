<?php

/**
 * Exemplo de uso de SubMerchant (Facilitadores de Pagamento)
 *
 * Este exemplo demonstra como usar dados de subadquirente para
 * operação como Facilitador de Pagamento.
 *
 * Adquirentes integradas: Stone, GetNet, Cielo 1.5, Cielo 3,
 * PagSeguro, ERede e SafraPay.
 */

use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderItemDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\CreditCardDTO;
use Kaninstein\LaravelPagarme\DTOs\SubMerchantDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// ============================================================
// OPÇÃO 1: Usando submerchant configurado no .env
// ============================================================
//
// Configure no .env:
// PAGARME_SUBMERCHANT_ENABLED=true
// PAGARME_SUBMERCHANT_MCC=4444
// PAGARME_SUBMERCHANT_FACILITATOR_CODE=5555555
// ... (outros campos)
//
// O submerchant será incluído AUTOMATICAMENTE em todos os pedidos

$order1 = new OrderDTO(
    items: [
        new OrderItemDTO(
            amount: 10000,
            description: 'Produto X',
            quantity: 1
        )
    ],
    customer: 'cus_123456',
    payments: [
        PaymentDTO::creditCard(
            creditCard: new CreditCardDTO(/* ... */),
            installments: 1
        )
    ]
);

// Submerchant será incluído automaticamente se enabled=true no config
$result1 = Pagarme::orders()->create($order1->toArray());


// ============================================================
// OPÇÃO 2: Definindo submerchant manualmente com DTO
// ============================================================

$submerchant = new SubMerchantDTO(
    merchantCategoryCode: '5411', // Supermercados
    paymentFacilitatorCode: '123456789',
    code: 'STORE-001',
    name: 'Loja do João',
    document: '12345678000190',
    type: 'company',
    legalName: 'João Comércio LTDA',
    phone: PhoneDTO::brazilian('11', '987654321'),
    address: AddressDTO::brazilian(
        street: 'Rua Exemplo, 123',
        zipCode: '01234567',
        city: 'São Paulo',
        state: 'SP',
        complement: 'Loja 1'
    )
);

$order2 = new OrderDTO(
    items: [
        new OrderItemDTO(
            amount: 5000,
            description: 'Produto Y',
            quantity: 2
        )
    ],
    customer: 'cus_123456',
    payments: [
        PaymentDTO::creditCard(
            creditCard: new CreditCardDTO(/* ... */),
            installments: 1
        )
    ],
    submerchant: $submerchant
);

$result2 = Pagarme::orders()->create($order2->toArray());


// ============================================================
// OPÇÃO 3: Definindo submerchant manualmente com array
// ============================================================

$order3 = new OrderDTO(
    items: [
        new OrderItemDTO(
            amount: 15000,
            description: 'Produto Z',
            quantity: 1
        )
    ],
    customer: 'cus_123456',
    payments: [
        PaymentDTO::creditCard(
            creditCard: new CreditCardDTO(/* ... */),
            installments: 1
        )
    ],
    submerchant: [
        'merchant_category_code' => '5411',
        'payment_facilitator_code' => '123456789',
        'code' => 'STORE-002',
        'name' => 'Loja da Maria',
        'legal_name' => 'Maria Comércio LTDA',
        'document' => '98765432000100',
        'type' => 'company',
        'phone' => [
            'country_code' => '55',
            'area_code' => '21',
            'number' => '987654321'
        ],
        'address' => [
            'street' => 'Avenida Brasil',
            'number' => '1000',
            'neighborhood' => 'Centro',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
            'country' => 'BR',
            'zip_code' => '20000000'
        ]
    ]
);

$result3 = Pagarme::orders()->create($order3->toArray());


// ============================================================
// OPÇÃO 4: Helper brasileiro
// ============================================================

$submerchantBr = SubMerchantDTO::brazilian(
    merchantCategoryCode: '5411',
    paymentFacilitatorCode: '123456789',
    code: 'STORE-003',
    name: 'Loja do Pedro',
    document: '11111111000111',
    type: 'company',
    legalName: 'Pedro Comércio LTDA',
    phone: PhoneDTO::parseBrazilian('(11) 98765-4321'),
    address: AddressDTO::brazilian(
        street: 'Rua das Flores, 456',
        zipCode: '04567890',
        city: 'São Paulo',
        state: 'SP'
    )
);

$order4 = new OrderDTO(
    items: [/* ... */],
    customer: 'cus_123456',
    payments: [/* ... */],
    submerchant: $submerchantBr
);


// ============================================================
// OPÇÃO 5: Desabilitar submerchant para um pedido específico
// (mesmo que esteja habilitado no config)
// ============================================================

$order5 = new OrderDTO(
    items: [/* ... */],
    customer: 'cus_123456',
    payments: [/* ... */]
);

// Desabilitar submerchant para este pedido específico
$order5->withoutSubmerchant();

$result5 = Pagarme::orders()->create($order5->toArray());


// ============================================================
// OPÇÃO 6: Definir submerchant dinamicamente
// ============================================================

$order6 = new OrderDTO(
    items: [/* ... */],
    customer: 'cus_123456',
    payments: [/* ... */]
);

// Adicionar submerchant depois da criação do DTO
$order6->withSubmerchant(
    new SubMerchantDTO(
        merchantCategoryCode: '5912',
        paymentFacilitatorCode: '987654321',
        code: 'STORE-004',
        name: 'Loja da Ana',
        document: '22222222000122',
        type: 'company'
    )
);

$result6 = Pagarme::orders()->create($order6->toArray());


// ============================================================
// EXEMPLO COMPLETO com todos os dados
// ============================================================

echo "Criando pedido como Facilitador de Pagamento...\n";

$customer = new CustomerDTO(
    name: 'Tony Stark',
    email: 'tony@starkindustries.com',
    document: '12345678900',
    documentType: 'CPF'
);

$item = new OrderItemDTO(
    amount: 2990, // R$ 29,90
    description: 'Chaveiro do Tesseract',
    quantity: 1
);

$creditCard = new CreditCardDTO(
    number: '4000000000000010',
    holderName: 'TONY STARK',
    expMonth: 1,
    expYear: 25,
    cvv: '351',
    billingAddress: [
        'street' => 'Malibu Point',
        'number' => '10880',
        'zip_code' => '90265',
        'neighborhood' => 'Central Malibu',
        'city' => 'Malibu',
        'state' => 'CA',
        'country' => 'US'
    ]
);

$payment = PaymentDTO::creditCard(
    creditCard: $creditCard,
    installments: 1,
    statementDescriptor: 'AVENGERS'
);

$submerchant = new SubMerchantDTO(
    merchantCategoryCode: '4444',
    paymentFacilitatorCode: '5555555',
    code: 'code2',
    name: 'Sub Tony Stark',
    document: '123456789',
    type: 'individual',
    legalName: 'Empresa LTDA',
    phone: PhoneDTO::brazilian('21', '000000000'),
    address: AddressDTO::brazilian(
        street: 'Malibu Point',
        zipCode: '24210460',
        city: 'Malibu',
        state: 'CA',
        complement: 'A'
    )
);

$order = new OrderDTO(
    items: [$item],
    customer: $customer,
    payments: [$payment],
    ip: '192.168.0.1',
    sessionId: 'session_id_test',
    location: [
        'latitude' => '10',
        'longitude' => '20'
    ],
    device: [
        'platform' => 'android os'
    ],
    submerchant: $submerchant,
    shipping: [
        'amount' => 110,
        'description' => 'Entrega padrão',
        'recipient_name' => 'Marcelo',
        'type' => 'standard',
        'address' => [
            'street' => 'Malibu Point',
            'number' => '10882',
            'zip_code' => '90265',
            'neighborhood' => 'Central Malibu',
            'city' => 'Malibu',
            'state' => 'CA',
            'country' => 'US'
        ]
    ]
);

try {
    $result = Pagarme::orders()->create($order->toArray());

    echo "✅ Pedido criado com sucesso!\n";
    echo "ID: {$result['id']}\n";
    echo "Status: {$result['status']}\n";

} catch (\Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n";
}
