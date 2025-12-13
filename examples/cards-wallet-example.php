<?php

/**
 * Exemplo completo de gerenciamento de Cartões (Wallet)
 *
 * Este exemplo demonstra todas as operações disponíveis para
 * gerenciamento da carteira de cartões dos clientes.
 */

use Kaninstein\LaravelPagarme\DTOs\CreditCardDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\LaravelPagarme\Services\TokenService;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;

echo "=== Gerenciamento de Cartões (Wallet) - Pagarme ===\n\n";

// Assumindo que já temos um cliente criado
$customerId = 'cus_123456'; // Substituir por ID real

// ============================================================
// 1. CRIAR CARTÃO NA WALLET
// ============================================================

echo "1. Adicionando cartão à wallet do cliente...\n";

$newCard = new CreditCardDTO(
    number: '4111111111111111',
    holderName: 'JOAO SILVA',
    holderDocument: '12345678900',
    expMonth: 12,
    expYear: 2025,
    cvv: '123',
    billingAddress: AddressDTO::brazilian(
        street: 'Rua das Flores, 123',
        zipCode: '01234567',
        city: 'São Paulo',
        state: 'SP'
    ),
    metadata: [
        'card_nickname' => 'Cartão Principal',
        'is_default' => true
    ]
);

// Validar antes de enviar
if (!$newCard->isValid()) {
    $errors = $newCard->validate();
    echo "❌ Erros de validação:\n";
    print_r($errors);
    exit;
}

try {
    $card = Pagarme::cards()->create($customerId, $newCard->toArray());

    echo "✅ Cartão adicionado com sucesso!\n";
    echo "   ID: {$card['id']}\n";
    echo "   Últimos 4 dígitos: {$card['last_four_digits']}\n";
    echo "   Primeiros 6 dígitos: {$card['first_six_digits']}\n";
    echo "   Bandeira: {$card['brand']}\n";
    echo "   Status: {$card['status']}\n\n";

    $cardId = $card['id'];
} catch (PagarmeException $e) {
    // Erro 412: Falha na verificação do cartão
    if ($e->getCode() === 412) {
        echo "❌ Falha na verificação do cartão!\n";
        echo "   Mensagem: {$e->getMessage()}\n\n";
    } else {
        echo "❌ Erro: {$e->getMessage()}\n\n";
    }
}


// ============================================================
// 2. COMPORTAMENTO: CARTÃO DUPLICADO
// ============================================================

echo "2. Testando cartão duplicado...\n";
echo "⚠️  IMPORTANTE: Se tentar adicionar o mesmo cartão duas vezes,\n";
echo "   a API retorna o mesmo card_id do cartão existente!\n\n";

$duplicateCard = new CreditCardDTO(
    number: '4111111111111111', // Mesmo número
    holderName: 'JOAO SILVA',
    expMonth: 12,
    expYear: 2025,
    cvv: '123',
    billingAddress: ['line_1' => 'Rua X', 'zip_code' => '01234567', 'city' => 'SP', 'state' => 'SP', 'country' => 'BR']
);

$duplicatedCard = Pagarme::cards()->create($customerId, $duplicateCard->toArray());
echo "Card ID retornado: {$duplicatedCard['id']}\n";
echo "É o mesmo ID? " . ($duplicatedCard['id'] === $cardId ? 'SIM' : 'NÃO') . "\n\n";


// ============================================================
// 3. LISTAR CARTÕES (WALLET)
// ============================================================

echo "3. Listando todos os cartões do cliente (Wallet)...\n";

$wallet = Pagarme::cards()->list($customerId);

echo "Total de cartões na wallet: " . count($wallet['data']) . "\n\n";

foreach ($wallet['data'] as $c) {
    echo "  - {$c['brand']} **** {$c['last_four_digits']}\n";
    echo "    Status: {$c['status']}\n";
    echo "    Validade: {$c['exp_month']}/{$c['exp_year']}\n";
    echo "    ID: {$c['id']}\n\n";
}


// ============================================================
// 4. BUSCAR CARTÃO ESPECÍFICO
// ============================================================

echo "4. Buscando cartão específico...\n";

$specificCard = Pagarme::cards()->get($customerId, $cardId);

echo "✅ Cartão encontrado:\n";
echo "   Titular: {$specificCard['holder_name']}\n";
echo "   Bandeira: {$specificCard['brand']}\n";
echo "   Tipo: {$specificCard['type']}\n";
echo "   Private Label: " . ($specificCard['private_label'] ? 'Sim' : 'Não') . "\n\n";


// ============================================================
// 5. ATUALIZAR CARTÃO
// ============================================================

echo "5. Atualizando dados do cartão...\n";
echo "Campos que podem ser atualizados:\n";
echo "  - holder_name\n";
echo "  - holder_document\n";
echo "  - exp_month\n";
echo "  - exp_year\n";
echo "  - billing_address_id\n\n";

// Atualizar data de validade
$updated = Pagarme::cards()->updateExpiration(
    $customerId,
    $cardId,
    expMonth: 12,
    expYear: 2026
);

echo "✅ Data de validade atualizada: {$updated['exp_month']}/{$updated['exp_year']}\n\n";

// Atualizar nome do titular
$updated = Pagarme::cards()->updateHolderName(
    $customerId,
    $cardId,
    holderName: 'JOAO PEDRO SILVA'
);

echo "✅ Nome do titular atualizado: {$updated['holder_name']}\n\n";


// ============================================================
// 6. RENOVAR CARTÃO (CARD UPDATER MANUAL)
// ============================================================

echo "6. Renovando cartão usando Card Updater...\n";

try {
    $renewed = Pagarme::cards()->renew($customerId, $cardId);
    echo "✅ Cartão renovado com sucesso!\n";
    echo "   Nova validade: {$renewed['exp_month']}/{$renewed['exp_year']}\n\n";
} catch (PagarmeException $e) {
    // Erro 412 se a renovação falhar
    echo "⚠️  Renovação não disponível para este cartão\n\n";
}


// ============================================================
// 7. TOKENIZAÇÃO DE CARTÃO
// ============================================================

echo "7. Tokenizando cartão...\n";
echo "⚠️  IMPORTANTE - SEGURANÇA:\n";
echo "   1. Tokenização usa PUBLIC_KEY (não secret key!)\n";
echo "   2. Apenas header Content-Type é permitido\n";
echo "   3. Public key vai como parâmetro 'appId' na query\n";
echo "   4. Domínio deve estar registrado no dashboard\n";
echo "   5. Billing address NÃO é tokenizado!\n\n";

// Preparar dados do cartão para tokenização
$cardToTokenize = TokenService::prepareCardData(
    number: '5555555555554444',
    holderName: 'MARIA SANTOS',
    expMonth: 6,
    expYear: 2025,
    cvv: '321',
    holderDocument: '98765432100'
);

try {
    // Criar token
    $tokenResult = Pagarme::tokens()->createCardToken($cardToTokenize);
    $token = $tokenResult['id'];

    echo "✅ Cartão tokenizado com sucesso!\n";
    echo "   Token: {$token}\n\n";

    // Usar token para criar cartão
    echo "Adicionando cartão tokenizado à wallet...\n";

    $tokenizedCard = CreditCardDTO::fromToken(
        token: $token,
        billingAddress: AddressDTO::brazilian(
            street: 'Av. Paulista, 1000',
            zipCode: '01310100',
            city: 'São Paulo',
            state: 'SP'
        )
    );

    $cardFromToken = Pagarme::cards()->create($customerId, $tokenizedCard->toArray());
    echo "✅ Cartão tokenizado adicionado: {$cardFromToken['id']}\n\n";

} catch (PagarmeException $e) {
    echo "❌ Erro na tokenização: {$e->getMessage()}\n\n";
}


// ============================================================
// 8. CARTÕES DE VOUCHER (VR, Pluxee, Ticket, Alelo)
// ============================================================

echo "8. Adicionando cartão voucher...\n";

$voucherCard = new CreditCardDTO(
    number: '6062825624254001', // Número de exemplo
    holderName: 'JOAO SILVA',
    holderDocument: '12345678900', // OBRIGATÓRIO para voucher!
    expMonth: 12,
    expYear: 2025,
    cvv: '123',
    type: 'voucher',
    brand: 'VR', // VR, Pluxee, Ticket ou Alelo
    billingAddress: [
        'line_1' => 'Rua X',
        'zip_code' => '01234567',
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR'
    ]
);

// Validar (holder_document é obrigatório para voucher)
if (!$voucherCard->isValid()) {
    echo "❌ Erros: " . json_encode($voucherCard->validate()) . "\n\n";
} else {
    echo "✅ Cartão voucher válido\n\n";
}


// ============================================================
// 9. CARTÕES PRIVATE LABEL
// ============================================================

echo "9. Adicionando cartão Private Label...\n";

$privateLabelCard = new CreditCardDTO(
    number: '1234567890123456',
    holderName: 'JOAO SILVA',
    expMonth: 12,
    expYear: 2025,
    cvv: '123',
    privateLabel: true,
    brand: 'CustomBrand', // OBRIGATÓRIO para private label!
    billingAddress: [
        'line_1' => 'Rua X',
        'zip_code' => '01234567',
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR'
    ]
);

// Validar (brand é obrigatório para private label)
if (!$privateLabelCard->isValid()) {
    echo "❌ Erros: " . json_encode($privateLabelCard->validate()) . "\n\n";
} else {
    echo "✅ Cartão private label válido\n\n";
}


// ============================================================
// 10. USAR CARTÃO SALVO EM PAGAMENTO
// ============================================================

echo "10. Usando cartão salvo em pagamento...\n";

// Opção 1: Usar card_id diretamente
$savedCard1 = CreditCardDTO::fromCardId($cardId);

// Opção 2: Com options
$savedCard2 = CreditCardDTO::fromCardId($cardId, options: [
    'verify_card' => false
]);

echo "Cartão configurado para uso em pagamento\n";
echo "Card ID: {$savedCard1->cardId}\n\n";


// ============================================================
// 11. DELETAR CARTÃO DA WALLET
// ============================================================

echo "11. Removendo cartão da wallet...\n";

try {
    $deleted = Pagarme::cards()->delete($customerId, $cardId);

    echo "✅ Cartão removido com sucesso!\n";
    echo "   ID: {$deleted['id']}\n";
    echo "   Status: {$deleted['status']}\n";
    echo "   Deletado em: {$deleted['deleted_at']}\n\n";

} catch (PagarmeException $e) {
    echo "❌ Erro ao deletar: {$e->getMessage()}\n\n";
}


// ============================================================
// 12. BANDEIRAS SUPORTADAS
// ============================================================

echo "12. Bandeiras suportadas:\n\n";

echo "Crédito:\n";
echo "  - Elo, Mastercard, Visa, Amex\n";
echo "  - JCB, Aura, Hipercard, Diners\n";
echo "  - Discover, UnionPay\n\n";

echo "Voucher:\n";
echo "  - VR, Pluxee, Ticket, Alelo\n\n";


// ============================================================
// 13. VALIDAÇÃO COMPLETA
// ============================================================

echo "13. Exemplo de validação completa...\n";

$invalidCard = new CreditCardDTO(
    number: '123', // Muito curto (min 13)
    holderName: str_repeat('A', 100), // Muito longo (max 64)
    expMonth: 13, // Inválido (max 12)
    cvv: '12', // Inválido (3 ou 4)
    type: 'voucher',
    // holder_document faltando (obrigatório para voucher)
    privateLabel: true
    // brand faltando (obrigatório para private label)
);

$errors = $invalidCard->validate();

echo "Erros encontrados:\n";
foreach ($errors as $field => $error) {
    echo "  - {$field}: {$error}\n";
}

echo "\n";


echo "=== Fim do Exemplo ===\n";
