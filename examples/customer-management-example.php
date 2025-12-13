<?php

/**
 * Exemplo completo de gerenciamento de clientes
 *
 * Este exemplo demonstra todas as operações disponíveis para
 * gerenciamento de clientes (carteira de clientes).
 */

use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\DTOs\PhonesDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\LaravelPagarme\Exceptions\ValidationException;

echo "=== Gerenciamento de Clientes - Pagarme ===\n\n";

// ============================================================
// 1. CRIAR CLIENTE - Pessoa Física (Individual)
// ============================================================

echo "1. Criando cliente pessoa física...\n";

$customerPF = CustomerDTO::individual(
    name: 'João Silva',
    email: 'joao.silva@example.com',
    cpf: '12345678900',
    phone: PhonesDTO::mobile(
        PhoneDTO::parseBrazilian('(11) 98765-4321')
    ),
    address: AddressDTO::brazilian(
        street: 'Rua das Flores, 123',
        zipCode: '01234567',
        city: 'São Paulo',
        state: 'SP',
        complement: 'Apto 42'
    )
);

// Adicionar dados opcionais
$customerPF->gender = 'male';
$customerPF->birthdate = '01/15/1990'; // mm/dd/yyyy
$customerPF->code = 'CUST-001'; // Código no seu sistema
$customerPF->metadata = [
    'customer_tier' => 'gold',
    'source' => 'website',
    'campaign' => 'summer2025'
];

// Validar antes de enviar
if (!$customerPF->isValid()) {
    $errors = $customerPF->validate();
    echo "Erros de validação: " . json_encode($errors) . "\n";
    exit;
}

try {
    $createdPF = Pagarme::customers()->create($customerPF->toArray());
    echo "✅ Cliente PF criado: {$createdPF['id']}\n";
    echo "   Nome: {$createdPF['name']}\n";
    echo "   Email: {$createdPF['email']}\n\n";
} catch (ValidationException $e) {
    echo "❌ Erro de validação: {$e->getMessage()}\n";
    print_r($e->getErrors());
}


// ============================================================
// 2. CRIAR CLIENTE - Pessoa Jurídica (Company)
// ============================================================

echo "2. Criando cliente pessoa jurídica...\n";

$customerPJ = CustomerDTO::company(
    name: 'Tech Solutions LTDA',
    email: 'contato@techsolutions.com',
    cnpj: '12345678000190',
    phone: PhonesDTO::both(
        homePhone: PhoneDTO::brazilian('11', '33334444'),
        mobilePhone: PhoneDTO::brazilian('11', '987654321')
    ),
    address: AddressDTO::brazilian(
        street: 'Avenida Paulista, 1000',
        zipCode: '01310100',
        city: 'São Paulo',
        state: 'SP'
    )
);

$customerPJ->code = 'CORP-001';
$customerPJ->metadata = [
    'industry' => 'technology',
    'employee_count' => '50-100'
];

try {
    $createdPJ = Pagarme::customers()->create($customerPJ->toArray());
    echo "✅ Cliente PJ criado: {$createdPJ['id']}\n\n";
} catch (\Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n\n";
}


// ============================================================
// 3. IMPORTANTE: Email é ÚNICO
// ============================================================

echo "3. Testando unicidade de email...\n";
echo "⚠️  IMPORTANTE: Se você criar um cliente com email existente,\n";
echo "   a API irá ATUALIZAR o cliente existente ao invés de criar novo!\n\n";

// Tentando criar com mesmo email - vai atualizar o cliente existente
$duplicateEmail = new CustomerDTO(
    name: 'João Silva Atualizado',
    email: 'joao.silva@example.com', // Mesmo email
    document: '12345678900',
    documentType: 'CPF'
);

$updated = Pagarme::customers()->create($duplicateEmail->toArray());
echo "Cliente com email existente foi atualizado!\n";
echo "ID (mesmo de antes): {$updated['id']}\n";
echo "Nome atualizado: {$updated['name']}\n\n";


// ============================================================
// 4. BUSCAR CLIENTE POR ID
// ============================================================

echo "4. Buscando cliente por ID...\n";

$customerId = $createdPF['id'];
$customer = Pagarme::customers()->get($customerId);

echo "✅ Cliente encontrado:\n";
echo "   ID: {$customer['id']}\n";
echo "   Nome: {$customer['name']}\n";
echo "   Email: {$customer['email']}\n";
echo "   Tipo: {$customer['type']}\n";
echo "   Criado em: {$customer['created_at']}\n\n";


// ============================================================
// 5. ATUALIZAR CLIENTE
// ============================================================

echo "5. Atualizando cliente...\n";
echo "⚠️  WARNING: PUT substitui TODOS os dados!\n";
echo "   Campos não enviados serão setados como NULL.\n\n";

// Buscar dados completos primeiro
$currentCustomer = Pagarme::customers()->get($customerId);

// Atualizar mantendo todos os dados
$updateData = CustomerDTO::fromArray($currentCustomer);
$updateData->name = 'João Silva Santos'; // Alterar apenas o nome
$updateData->birthdate = '01/15/1990';
$updateData->gender = 'male';

$updatedCustomer = Pagarme::customers()->update(
    $customerId,
    $updateData->toArray()
);

echo "✅ Cliente atualizado!\n";
echo "   Nome antigo: {$currentCustomer['name']}\n";
echo "   Nome novo: {$updatedCustomer['name']}\n\n";


// ============================================================
// 6. LISTAR CLIENTES
// ============================================================

echo "6. Listando clientes...\n";

// Listar todos (paginado)
$allCustomers = Pagarme::customers()->list([
    'page' => 1,
    'size' => 10
]);

echo "Total de clientes: {$allCustomers['paging']['total']}\n";
echo "Clientes nesta página: " . count($allCustomers['data']) . "\n\n";

foreach ($allCustomers['data'] as $cust) {
    echo "  - {$cust['name']} ({$cust['email']})\n";
}
echo "\n";


// ============================================================
// 7. BUSCAR CLIENTES COM FILTROS
// ============================================================

echo "7. Buscando clientes com filtros...\n";

// Buscar por nome
$byName = Pagarme::customers()->searchByName('João');
echo "Clientes com nome 'João': " . count($byName['data']) . "\n";

// Buscar por email
$byEmail = Pagarme::customers()->searchByEmail('joao.silva@example.com');
echo "Clientes com email específico: " . count($byEmail['data']) . "\n";

// Buscar por documento
$byDocument = Pagarme::customers()->searchByDocument('12345678900');
echo "Clientes com CPF específico: " . count($byDocument['data']) . "\n";

// Buscar por código
$byCode = Pagarme::customers()->searchByCode('CUST-001');
echo "Clientes com código CUST-001: " . count($byCode['data']) . "\n";

// Filtrar por gênero
$byGender = Pagarme::customers()->filterByGender('male');
echo "Clientes masculinos: " . count($byGender['data']) . "\n\n";


// ============================================================
// 8. GERENCIAR CARTÕES DO CLIENTE
// ============================================================

echo "8. Gerenciando cartões do cliente...\n";

// Adicionar cartão
$cardData = [
    'number' => '4111111111111111',
    'holder_name' => 'JOAO SILVA',
    'exp_month' => 12,
    'exp_year' => 2025,
    'cvv' => '123',
    'billing_address' => [
        'line_1' => 'Rua das Flores, 123',
        'zip_code' => '01234567',
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR'
    ]
];

try {
    $card = Pagarme::customers()->createCard($customerId, $cardData);
    echo "✅ Cartão adicionado: {$card['id']}\n";
    echo "   Últimos 4 dígitos: {$card['last_four_digits']}\n";
    echo "   Bandeira: {$card['brand']}\n\n";

    // Listar cartões do cliente
    $cards = Pagarme::customers()->cards($customerId);
    echo "Total de cartões: " . count($cards['data']) . "\n\n";

} catch (\Exception $e) {
    echo "❌ Erro ao adicionar cartão: {$e->getMessage()}\n\n";
}


// ============================================================
// 9. GERENCIAR ENDEREÇOS DO CLIENTE
// ============================================================

echo "9. Gerenciando endereços do cliente...\n";

// Adicionar endereço
$addressData = AddressDTO::brazilian(
    street: 'Rua Nova, 456',
    zipCode: '04567890',
    city: 'São Paulo',
    state: 'SP',
    complement: 'Casa 2'
);

try {
    $address = Pagarme::customers()->createAddress(
        $customerId,
        $addressData->toArray()
    );
    echo "✅ Endereço adicionado: {$address['id']}\n";

    // Listar endereços
    $addresses = Pagarme::customers()->addresses($customerId);
    echo "Total de endereços: " . count($addresses['data']) . "\n\n";

    // Atualizar endereço
    $updatedAddress = Pagarme::customers()->updateAddress(
        $customerId,
        $address['id'],
        array_merge($addressData->toArray(), ['complement' => 'Casa 3'])
    );
    echo "✅ Endereço atualizado\n\n";

} catch (\Exception $e) {
    echo "❌ Erro com endereços: {$e->getMessage()}\n\n";
}


// ============================================================
// 10. CLIENTES COM PASSAPORTE (INTERNACIONAL)
// ============================================================

echo "10. Criando cliente internacional com passaporte...\n";

$internationalCustomer = new CustomerDTO(
    name: 'John Doe',
    email: 'john.doe@example.com',
    type: 'individual',
    document: 'AB123456789',
    documentType: 'PASSPORT'
);

// Endereço internacional (ZIP Code americano)
$internationalCustomer->address = [
    'line_1' => '123 Main Street',
    'zip_code' => '10001', // ZIP Code americano
    'city' => 'New York',
    'state' => 'NY',
    'country' => 'US'
];

$internationalCustomer->metadata = [
    'nationality' => 'american',
    'visa_type' => 'tourist'
];

echo "⚠️  IMPORTANTE: Clientes com PASSPORT só podem usar endereços internacionais!\n";
echo "   Reconhecidos pelo ZIP Code de cada país.\n\n";

try {
    $intCustomer = Pagarme::customers()->create($internationalCustomer->toArray());
    echo "✅ Cliente internacional criado: {$intCustomer['id']}\n\n";
} catch (\Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n\n";
}


// ============================================================
// 11. VALIDAÇÃO DE DADOS
// ============================================================

echo "11. Testando validação de dados...\n";

$invalidCustomer = new CustomerDTO(
    name: str_repeat('A', 100), // Nome muito longo (max 64)
    email: str_repeat('test@', 20) . 'example.com', // Email muito longo
    type: 'invalid_type', // Tipo inválido
    documentType: 'RG' // Tipo de documento inválido
);

$errors = $invalidCustomer->validate();

if (!empty($errors)) {
    echo "❌ Erros de validação encontrados:\n";
    foreach ($errors as $field => $error) {
        echo "   - {$field}: {$error}\n";
    }
}

echo "\n";


// ============================================================
// 12. USANDO HELPERS
// ============================================================

echo "12. Usando helpers do CustomerDTO...\n";

// Helper para criar PF
$quickPF = CustomerDTO::individual(
    name: 'Maria Santos',
    email: 'maria@example.com',
    cpf: '98765432100'
);

echo "✅ Cliente PF criado com helper\n";

// Helper para criar PJ
$quickPJ = CustomerDTO::company(
    name: 'Empresa XYZ',
    email: 'empresa@xyz.com',
    cnpj: '11222333000144'
);

echo "✅ Cliente PJ criado com helper\n\n";


echo "=== Fim do Exemplo ===\n";
