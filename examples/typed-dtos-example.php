<?php

/**
 * Exemplo usando DTOs tipados para maior segurança de tipos
 */

use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\DTOs\PhonesDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// Exemplo 1: Criar cliente com DTOs tipados
$phones = PhonesDTO::mobile(
    PhoneDTO::brazilian('11', '987654321')
);

// Ou usando o parser de telefone brasileiro
$phones = PhonesDTO::mobile(
    PhoneDTO::parseBrazilian('(11) 98765-4321')
);

// Ou com ambos os telefones
$phones = PhonesDTO::both(
    homePhone: PhoneDTO::brazilian('11', '33334444'),
    mobilePhone: PhoneDTO::brazilian('11', '987654321')
);

$address = AddressDTO::brazilian(
    street: 'Avenida Paulista, 1000',
    zipCode: '01310100',
    city: 'São Paulo',
    state: 'SP',
    complement: 'Conjunto 42'
);

$customerDTO = new CustomerDTO(
    name: 'Maria Silva',
    email: 'maria@example.com',
    document: '12345678900',
    documentType: 'CPF',
    phones: $phones,
    address: $address,
    metadata: [
        'customer_tier' => 'premium',
        'signup_date' => now()->toDateString()
    ]
);

$customer = Pagarme::customers()->create($customerDTO->toArray());

echo "Cliente criado com DTOs tipados: {$customer['id']}\n";

// Exemplo 2: Formato alternativo (backward compatible com arrays)
$customerArray = new CustomerDTO(
    name: 'João Santos',
    email: 'joao@example.com',
    phones: [
        'mobile_phone' => [
            'country_code' => '55',
            'area_code' => '21',
            'number' => '999887766'
        ]
    ],
    address: [
        'line_1' => 'Rua das Flores, 100',
        'zip_code' => '20000000',
        'city' => 'Rio de Janeiro',
        'state' => 'RJ',
        'country' => 'BR'
    ]
);

$customer2 = Pagarme::customers()->create($customerArray->toArray());

echo "Cliente criado com arrays: {$customer2['id']}\n";
