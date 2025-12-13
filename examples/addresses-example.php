<?php

/**
 * Exemplo completo de gerenciamento de Endereços
 *
 * Este exemplo demonstra o formato correto de endereços
 * conforme especificação da API Pagarme.
 */

use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\Facades\Pagarme;

echo "=== Gerenciamento de Endereços - Pagarme ===\n\n";

// Assumindo que já temos um cliente criado
$customerId = 'cus_123456'; // Substituir por ID real

// ============================================================
// 1. FORMATO CORRETO DO line_1
// ============================================================

echo "1. Entendendo o formato correto do line_1...\n\n";

echo "⚠️  IMPORTANTE: Formatação do Endereço\n";
echo "line_1 deve seguir o formato: 'Número, Rua, Bairro'\n";
echo "  (nesta ordem e separados por vírgula)\n";
echo "line_2 contém complemento: andar, apto, sala, etc.\n\n";

echo "❌ CAMPOS DESCONTINUADOS (NÃO USAR!):\n";
echo "  - street, number, complement, neighborhood\n";
echo "  Esses campos serão removidos em breve!\n\n";

// Exemplo de formato correto
$exampleLine1 = "375, Av. General Justo, Centro";
$exampleLine2 = "7º andar, sala 01";

echo "✅ Exemplo CORRETO:\n";
echo "  line_1: \"{$exampleLine1}\"\n";
echo "  line_2: \"{$exampleLine2}\"\n\n";


// ============================================================
// 2. CRIAR ENDEREÇO BRASILEIRO (FORMATO CORRETO)
// ============================================================

echo "2. Criando endereço brasileiro com formato correto...\n";

$address = AddressDTO::brazilian(
    number: '375',
    street: 'Av. General Justo',
    neighborhood: 'Centro',
    zipCode: '20021130', // Apenas números!
    city: 'Rio de Janeiro',
    state: 'RJ', // Código ISO 3166-2
    complement: '7º andar, sala 01',
    metadata: [
        'address_type' => 'commercial',
        'building_name' => 'Edifício Exemplo'
    ]
);

echo "Endereço criado:\n";
echo "  line_1: {$address->line1}\n";
echo "  line_2: {$address->line2}\n";
echo "  ZIP: {$address->zipCode}\n";
echo "  Cidade: {$address->city}\n";
echo "  Estado: {$address->state}\n\n";

// Validar antes de enviar
if (!$address->isValid()) {
    $errors = $address->validate();
    echo "❌ Erros de validação:\n";
    print_r($errors);
} else {
    echo "✅ Endereço válido!\n\n";
}

// Adicionar à wallet do cliente
try {
    $created = Pagarme::customers()->createAddress($customerId, $address->toArray());

    echo "✅ Endereço adicionado à wallet do cliente!\n";
    echo "   ID: {$created['id']}\n";
    echo "   Status: {$created['status']}\n\n";

    $addressId = $created['id'];
} catch (\Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n\n";
}


// ============================================================
// 3. ENDEREÇO SEM NÚMERO
// ============================================================

echo "3. Criando endereço sem número...\n";

$addressWithoutNumber = AddressDTO::brazilian(
    number: '', // Sem número - pode enviar vazio
    street: 'Rua das Palmeiras',
    neighborhood: 'Jardim América',
    zipCode: '01234567',
    city: 'São Paulo',
    state: 'SP',
    complement: 'Loja A'
);

echo "Endereço sem número:\n";
echo "  line_1: \"{$addressWithoutNumber->line1}\"\n";
echo "  (O número foi omitido conforme permitido)\n\n";


// ============================================================
// 4. FORMATAR CEP AUTOMATICAMENTE
// ============================================================

echo "4. Formatação automática de CEP...\n";

// CEP com formatação será limpo automaticamente
$cepFormatado = '01310-100';
$cepLimpo = AddressDTO::formatCep($cepFormatado);

echo "CEP original: {$cepFormatado}\n";
echo "CEP formatado: {$cepLimpo}\n";
echo "(Apenas números, conforme API Pagarme)\n\n";

$addressAutoFormat = AddressDTO::brazilian(
    number: '1000',
    street: 'Av. Paulista',
    neighborhood: 'Bela Vista',
    zipCode: '01310-100', // Será limpo automaticamente
    city: 'São Paulo',
    state: 'SP'
);

echo "ZIP Code no objeto: {$addressAutoFormat->zipCode}\n\n";


// ============================================================
// 5. ENDEREÇO INTERNACIONAL (CLIENTES COM PASSAPORTE)
// ============================================================

echo "5. Criando endereço internacional...\n";
echo "⚠️  Clientes com PASSAPORTE só podem usar endereços internacionais!\n";
echo "   Não é possível usar passaporte + endereço nacional.\n\n";

$internationalAddress = AddressDTO::international(
    line1: '123, Main Street, Downtown',
    zipCode: '10001', // ZIP Code americano
    city: 'New York',
    state: 'NY',
    countryCode: 'US', // ISO 3166-1 alpha-2
    line2: 'Apt 45B',
    metadata: [
        'address_type' => 'residential'
    ]
);

echo "Endereço internacional criado:\n";
echo "  line_1: {$internationalAddress->line1}\n";
echo "  ZIP Code: {$internationalAddress->zipCode}\n";
echo "  País: {$internationalAddress->country}\n\n";


// ============================================================
// 6. LISTAR ENDEREÇOS DO CLIENTE
// ============================================================

echo "6. Listando endereços do cliente...\n";

$addresses = Pagarme::customers()->addresses($customerId);

echo "Total de endereços: " . count($addresses['data']) . "\n\n";

foreach ($addresses['data'] as $addr) {
    echo "  - {$addr['line_1']}\n";
    echo "    {$addr['city']} - {$addr['state']}\n";
    echo "    CEP: {$addr['zip_code']}\n";
    echo "    ID: {$addr['id']}\n";
    echo "    Status: {$addr['status']}\n\n";
}


// ============================================================
// 7. BUSCAR ENDEREÇO ESPECÍFICO
// ============================================================

echo "7. Buscando endereço específico...\n";

$specificAddress = Pagarme::customers()->addresses($customerId);
if (!empty($specificAddress['data'])) {
    $addr = $specificAddress['data'][0];

    echo "✅ Endereço encontrado:\n";
    echo "   line_1: {$addr['line_1']}\n";
    echo "   line_2: " . ($addr['line_2'] ?? 'N/A') . "\n";
    echo "   Cidade: {$addr['city']}/{$addr['state']}\n";
    echo "   CEP: {$addr['zip_code']}\n\n";

    // Parse line_1 em componentes
    $parsedAddr = AddressDTO::fromArray($addr);
    $components = $parsedAddr->parseLine1();

    echo "Componentes do line_1:\n";
    echo "  Número: {$components['number']}\n";
    echo "  Rua: {$components['street']}\n";
    echo "  Bairro: {$components['neighborhood']}\n\n";
}


// ============================================================
// 8. ATUALIZAR ENDEREÇO
// ============================================================

echo "8. Atualizando endereço...\n";
echo "⚠️  Apenas line_2 e metadata podem ser atualizados!\n\n";

try {
    $updated = Pagarme::customers()->updateAddress(
        $customerId,
        $addressId,
        [
            'line_2' => '8º andar, sala 10', // Novo complemento
            'metadata' => [
                'updated_at' => now()->toDateString(),
                'address_type' => 'commercial'
            ]
        ]
    );

    echo "✅ Endereço atualizado!\n";
    echo "   Novo line_2: {$updated['line_2']}\n\n";
} catch (\Exception $e) {
    echo "❌ Erro: {$e->getMessage()}\n\n";
}


// ============================================================
// 9. ENDEREÇO PARA BILLING (CARTÕES)
// ============================================================

echo "9. Criando endereço para billing (cartões)...\n";

$billingAddress = AddressDTO::forBilling(
    number: '1000',
    street: 'Av. Paulista',
    neighborhood: 'Bela Vista',
    zipCode: '01310100',
    city: 'São Paulo',
    state: 'SP',
    country: 'BR'
);

echo "Endereço de cobrança criado:\n";
echo "  line_1: {$billingAddress->line1}\n";
echo "(Pronto para uso em cartões de crédito)\n\n";


// ============================================================
// 10. VALIDAÇÃO DE ENDEREÇOS
// ============================================================

echo "10. Testando validação...\n";

$invalidAddress = new AddressDTO(
    line1: str_repeat('A', 300), // Muito longo (max 256)
    zipCode: 'ABC12345', // Não numérico
    city: str_repeat('B', 100), // Muito longo (max 64)
    state: 'SP',
    country: 'BR',
    line2: str_repeat('C', 150), // Muito longo (max 128)
);

$errors = $invalidAddress->validate();

echo "Erros encontrados:\n";
foreach ($errors as $field => $error) {
    echo "  - {$field}: {$error}\n";
}
echo "\n";


// ============================================================
// 11. PAGINAÇÃO DE ENDEREÇOS
// ============================================================

echo "11. Listando endereços com paginação...\n";

$page1 = Pagarme::customers()->addresses($customerId);
// A API retorna estrutura de paginação

if (isset($page1['paging'])) {
    echo "Total de endereços: {$page1['paging']['total']}\n";
    echo "Tem próxima página? " . ($page1['paging']['next'] ? 'Sim' : 'Não') . "\n\n";
}


// ============================================================
// 12. DELETAR ENDEREÇO
// ============================================================

echo "12. Removendo endereço...\n";

try {
    $deleted = Pagarme::customers()->deleteAddress($customerId, $addressId);

    echo "✅ Endereço removido com sucesso!\n";
    echo "   ID: {$deleted['id']}\n";
    echo "   Status: {$deleted['status']}\n";
    echo "   Deletado em: {$deleted['deleted_at']}\n\n";
} catch (\Exception $e) {
    echo "❌ Erro ao deletar: {$e->getMessage()}\n\n";
}


// ============================================================
// 13. FORMATO LEGACY (DESCONTINUADO)
// ============================================================

echo "13. Formato legacy (DESCONTINUADO - NÃO USAR!)...\n";
echo "⚠️  Os campos street, number, complement, neighborhood\n";
echo "   serão descontinuados em breve pela Pagarme!\n\n";

// Se você ainda tem código antigo:
$legacyAddress = AddressDTO::fromLegacyFormat(
    street: 'Av. Paulista',
    number: '1000',
    neighborhood: 'Bela Vista',
    zipCode: '01310100',
    city: 'São Paulo',
    state: 'SP',
    complement: 'Conjunto 42'
);

echo "Convertido de formato legacy:\n";
echo "  line_1: {$legacyAddress->line1}\n";
echo "  line_2: {$legacyAddress->line2}\n";
echo "\n⚠️  Use AddressDTO::brazilian() ao invés disso!\n\n";


// ============================================================
// 14. CÓDIGOS ISO
// ============================================================

echo "14. Códigos ISO para estados e países...\n\n";

echo "Estados brasileiros (ISO 3166-2):\n";
echo "  SP, RJ, MG, RS, PR, SC, BA, CE, PE, etc.\n\n";

echo "Países (ISO 3166-1 alpha-2):\n";
echo "  BR (Brasil), US (EUA), UK (Reino Unido)\n";
echo "  AR (Argentina), UY (Uruguai), PY (Paraguai)\n";
echo "  etc.\n\n";


echo "=== Fim do Exemplo ===\n";
