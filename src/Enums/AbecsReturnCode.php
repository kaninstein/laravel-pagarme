<?php

namespace Kaninstein\LaravelPagarme\Enums;

/**
 * ABECS (Associação Brasileira das Empresas de Cartões de Crédito e Serviços)
 * standardized return codes for card transactions
 */
enum AbecsReturnCode: string
{
    // Approved Transactions (0000-0013)
    case APPROVED = '0000';
    case APPROVED_PARTIAL_AMOUNT = '0001';
    case APPROVED_VIP = '0002';
    case APPROVED_WITHOUT_SIGNATURE = '0003';
    case APPROVED_OFFLINE = '0013';

    // Generic Declines (1000-1099)
    case DECLINED_GENERIC = '1000';
    case DECLINED_INVALID_CARD = '1001';
    case DECLINED_SUSPECTED_FRAUD = '1002';
    case DECLINED_CONTACT_ACQUIRER = '1003';
    case DECLINED_RESTRICTED_CARD = '1004';
    case DECLINED_CONTACT_ISSUER = '1005';
    case DECLINED_TRIES_EXCEEDED = '1006';
    case DECLINED_SPECIAL_CONDITIONS = '1007';
    case DECLINED_LOST_CARD = '1008';
    case DECLINED_STOLEN_CARD = '1009';
    case DECLINED_INSUFFICIENT_FUNDS = '1016';
    case DECLINED_INVALID_TRANSACTION = '1017';
    case DECLINED_INVALID_AMOUNT = '1018';
    case DECLINED_INVALID_CVV = '1019';
    case DECLINED_AUTHENTICATION_FAILED = '1020';
    case DECLINED_CARD_NOT_PROCESSED = '1021';
    case DECLINED_INVALID_DATE = '1022';
    case DECLINED_INSTALLMENTS_ERROR = '1024';
    case DECLINED_UNREGISTERED_CARD = '1025';
    case DECLINED_INVALID_AUTHORIZATION = '1028';
    case DECLINED_INACTIVE_CARD = '1030';
    case DECLINED_BLOCKED_CARD = '1032';
    case DECLINED_EXPIRED_CARD = '1033';
    case DECLINED_INVALID_DATA = '1034';
    case DECLINED_TRANSACTION_NOT_ALLOWED = '1035';
    case DECLINED_WITHDRAWAL_AMOUNT_EXCEEDED = '1036';
    case DECLINED_INVALID_ISSUER = '1037';
    case DECLINED_REVERSAL_ERROR = '1038';
    case DECLINED_CRYPTOGRAPHY_ERROR = '1039';
    case DECLINED_ISSUER_UNAVAILABLE = '1040';
    case DECLINED_DUPLICATE_TRANSACTION = '1041';
    case DECLINED_CARD_NOT_EFFECTIVE = '1042';
    case DECLINED_CONFIRMED_FRAUD = '1043';
    case DECLINED_INVALID_ACCOUNT = '1051';
    case DECLINED_INVALID_ACCOUNT_TYPE = '1052';
    case DECLINED_INVALID_TRANSACTION_TYPE = '1053';
    case DECLINED_DAILY_LIMIT_EXCEEDED = '1054';
    case DECLINED_MONTHLY_LIMIT_EXCEEDED = '1055';
    case DECLINED_DAILY_WITHDRAWAL_LIMIT = '1056';
    case DECLINED_DAILY_WITHDRAWAL_COUNT = '1057';
    case DECLINED_INVALID_INSTALLMENT_VALUE = '1058';
    case DECLINED_EXCEEDS_MAX_INSTALLMENTS = '1059';
    case DECLINED_INVALID_CVV_LENGTH = '1061';
    case DECLINED_RESTRICTED_WALLET = '1062';
    case DECLINED_SECURITY_VIOLATION = '1063';
    case DECLINED_EXCEEDS_WITHDRAWAL_AMOUNT = '1064';
    case DECLINED_EXCEEDS_WITHDRAWAL_FREQUENCY = '1065';
    case DECLINED_ANTIFRAUD_REJECTED = '1070';
    case DECLINED_3DS_FAILED = '1071';

    // Internal Errors (5000-5097)
    case ERROR_GENERIC = '5000';
    case ERROR_ALREADY_REVERSED = '5001';
    case ERROR_PAYMENT_METHOD_NOT_ENABLED = '5002';
    case ERROR_INVALID_TRANSACTION = '5003';
    case ERROR_INVALID_AMOUNT = '5004';
    case ERROR_UNAUTHORIZED = '5005';
    case ERROR_ACQUIRER_TIMEOUT = '5006';
    case ERROR_ACQUIRER_ERROR = '5007';
    case ERROR_INVALID_CARD_NUMBER = '5008';
    case ERROR_INVALID_CVV = '5009';
    case ERROR_AUTHENTICATION_FAILED = '5010';
    case ERROR_CANCELED_TRANSACTION = '5011';
    case ERROR_TRANSACTION_NOT_FOUND = '5012';
    case ERROR_INVALID_MERCHANT = '5013';
    case ERROR_INVALID_ACQUIRER = '5014';
    case ERROR_COMMUNICATION_ERROR = '5015';
    case ERROR_DUPLICATED_ORDER_ID = '5021';
    case ERROR_CVV_REQUIRED = '5025';
    case ERROR_INVALID_MERCHANT_CATEGORY = '5034';
    case ERROR_SPLIT_NOT_ENABLED = '5041';
    case ERROR_INVALID_SPLIT = '5042';

    // System/Connection Issues (9000-9999)
    case TIMEOUT_ISSUER = '9111';
    case TIMEOUT_ACQUIRER = '9112';
    case IRREVERSIBLE_DECLINE = '9200';
    case SYSTEM_ERROR = '9999';

    /**
     * Get human-readable message for the code
     */
    public function getMessage(): string
    {
        return match ($this) {
            // Approved
            self::APPROVED => 'Transação aprovada com sucesso',
            self::APPROVED_PARTIAL_AMOUNT => 'Transação aprovada com valor parcial',
            self::APPROVED_VIP => 'Transação aprovada VIP',
            self::APPROVED_WITHOUT_SIGNATURE => 'Transação aprovada sem assinatura',
            self::APPROVED_OFFLINE => 'Transação aprovada offline',

            // Generic Declines
            self::DECLINED_GENERIC => 'Transação não autorizada. Contate o banco emissor',
            self::DECLINED_INVALID_CARD => 'Cartão vencido ou data de expiração incorreta',
            self::DECLINED_SUSPECTED_FRAUD => 'Transação com suspeita de fraude',
            self::DECLINED_CONTACT_ACQUIRER => 'Contate a adquirente',
            self::DECLINED_RESTRICTED_CARD => 'Cartão com restrições',
            self::DECLINED_CONTACT_ISSUER => 'Contate o banco emissor',
            self::DECLINED_TRIES_EXCEEDED => 'Número de tentativas de senha excedido',
            self::DECLINED_SPECIAL_CONDITIONS => 'Condições especiais - contate o emissor',
            self::DECLINED_LOST_CARD => 'Cartão reportado como perdido',
            self::DECLINED_STOLEN_CARD => 'Cartão reportado como roubado',
            self::DECLINED_INSUFFICIENT_FUNDS => 'Saldo/limite insuficiente',
            self::DECLINED_INVALID_TRANSACTION => 'Transação inválida',
            self::DECLINED_INVALID_AMOUNT => 'Valor da transação inválido',
            self::DECLINED_INVALID_CVV => 'CVV inválido',
            self::DECLINED_AUTHENTICATION_FAILED => 'Falha na autenticação',
            self::DECLINED_CARD_NOT_PROCESSED => 'Cartão não processado pelo emissor',
            self::DECLINED_INVALID_DATE => 'Data inválida',
            self::DECLINED_INSTALLMENTS_ERROR => 'Erro no parcelamento',
            self::DECLINED_UNREGISTERED_CARD => 'Cartão não cadastrado',
            self::DECLINED_INVALID_AUTHORIZATION => 'Código de autorização inválido',
            self::DECLINED_INACTIVE_CARD => 'Cartão inativo',
            self::DECLINED_BLOCKED_CARD => 'Cartão bloqueado',
            self::DECLINED_EXPIRED_CARD => 'Cartão vencido',
            self::DECLINED_INVALID_DATA => 'Dados do cartão inválidos',
            self::DECLINED_TRANSACTION_NOT_ALLOWED => 'Transação não permitida',
            self::DECLINED_WITHDRAWAL_AMOUNT_EXCEEDED => 'Valor de saque excedido',
            self::DECLINED_INVALID_ISSUER => 'Emissor inválido ou inexistente',
            self::DECLINED_REVERSAL_ERROR => 'Erro no estorno',
            self::DECLINED_CRYPTOGRAPHY_ERROR => 'Erro de criptografia',
            self::DECLINED_ISSUER_UNAVAILABLE => 'Emissor não disponível',
            self::DECLINED_DUPLICATE_TRANSACTION => 'Transação duplicada',
            self::DECLINED_CARD_NOT_EFFECTIVE => 'Cartão ainda não efetivado',
            self::DECLINED_CONFIRMED_FRAUD => 'Fraude confirmada',
            self::DECLINED_INVALID_ACCOUNT => 'Conta inválida',
            self::DECLINED_INVALID_ACCOUNT_TYPE => 'Tipo de conta inválido',
            self::DECLINED_INVALID_TRANSACTION_TYPE => 'Tipo de transação inválido',
            self::DECLINED_DAILY_LIMIT_EXCEEDED => 'Limite diário excedido',
            self::DECLINED_MONTHLY_LIMIT_EXCEEDED => 'Limite mensal excedido',
            self::DECLINED_DAILY_WITHDRAWAL_LIMIT => 'Limite diário de saque excedido',
            self::DECLINED_DAILY_WITHDRAWAL_COUNT => 'Número de saques diários excedido',
            self::DECLINED_INVALID_INSTALLMENT_VALUE => 'Valor de parcela inválido',
            self::DECLINED_EXCEEDS_MAX_INSTALLMENTS => 'Número máximo de parcelas excedido',
            self::DECLINED_INVALID_CVV_LENGTH => 'Tamanho do CVV inválido',
            self::DECLINED_RESTRICTED_WALLET => 'Carteira digital com restrições',
            self::DECLINED_SECURITY_VIOLATION => 'Violação de segurança',
            self::DECLINED_EXCEEDS_WITHDRAWAL_AMOUNT => 'Valor de saque excedido',
            self::DECLINED_EXCEEDS_WITHDRAWAL_FREQUENCY => 'Frequência de saque excedida',
            self::DECLINED_ANTIFRAUD_REJECTED => 'Transação rejeitada pelo antifraude',
            self::DECLINED_3DS_FAILED => 'Falha na autenticação 3D Secure',

            // Internal Errors
            self::ERROR_GENERIC => 'Erro genérico',
            self::ERROR_ALREADY_REVERSED => 'Transação já estornada',
            self::ERROR_PAYMENT_METHOD_NOT_ENABLED => 'Meio de pagamento não habilitado',
            self::ERROR_INVALID_TRANSACTION => 'Transação inválida',
            self::ERROR_INVALID_AMOUNT => 'Valor inválido',
            self::ERROR_UNAUTHORIZED => 'Não autorizado',
            self::ERROR_ACQUIRER_TIMEOUT => 'Timeout com a adquirente',
            self::ERROR_ACQUIRER_ERROR => 'Erro na adquirente',
            self::ERROR_INVALID_CARD_NUMBER => 'Número do cartão inválido',
            self::ERROR_INVALID_CVV => 'CVV inválido',
            self::ERROR_AUTHENTICATION_FAILED => 'Falha na autenticação',
            self::ERROR_CANCELED_TRANSACTION => 'Transação cancelada',
            self::ERROR_TRANSACTION_NOT_FOUND => 'Transação não encontrada',
            self::ERROR_INVALID_MERCHANT => 'Estabelecimento inválido',
            self::ERROR_INVALID_ACQUIRER => 'Adquirente inválida',
            self::ERROR_COMMUNICATION_ERROR => 'Erro de comunicação',
            self::ERROR_DUPLICATED_ORDER_ID => 'ID do pedido duplicado',
            self::ERROR_CVV_REQUIRED => 'CVV obrigatório',
            self::ERROR_INVALID_MERCHANT_CATEGORY => 'Categoria do estabelecimento inválida',
            self::ERROR_SPLIT_NOT_ENABLED => 'Split de pagamento não habilitado',
            self::ERROR_INVALID_SPLIT => 'Configuração de split inválida',

            // System/Connection Issues
            self::TIMEOUT_ISSUER => 'Timeout - Emissor não respondeu',
            self::TIMEOUT_ACQUIRER => 'Timeout - Adquirente não respondeu',
            self::IRREVERSIBLE_DECLINE => 'Recusa irreversível - Não tente novamente',
            self::SYSTEM_ERROR => 'Erro de sistema',
        };
    }

    /**
     * Check if transaction can be retried
     */
    public function canRetry(): bool
    {
        return match ($this) {
            // Cannot retry
            self::DECLINED_LOST_CARD,
            self::DECLINED_STOLEN_CARD,
            self::DECLINED_SUSPECTED_FRAUD,
            self::DECLINED_CONFIRMED_FRAUD,
            self::DECLINED_EXPIRED_CARD,
            self::DECLINED_BLOCKED_CARD,
            self::DECLINED_INACTIVE_CARD,
            self::DECLINED_INVALID_CARD,
            self::DECLINED_UNREGISTERED_CARD,
            self::DECLINED_CARD_NOT_EFFECTIVE,
            self::DECLINED_ANTIFRAUD_REJECTED,
            self::DECLINED_3DS_FAILED,
            self::IRREVERSIBLE_DECLINE,
            self::ERROR_DUPLICATED_ORDER_ID => false,

            // Can retry
            default => true,
        };
    }

    /**
     * Check if code indicates approval
     */
    public function isApproved(): bool
    {
        return str_starts_with($this->value, '0');
    }

    /**
     * Check if code indicates decline by issuer
     */
    public function isDeclined(): bool
    {
        return str_starts_with($this->value, '1');
    }

    /**
     * Check if code indicates internal error
     */
    public function isInternalError(): bool
    {
        return str_starts_with($this->value, '5');
    }

    /**
     * Check if code indicates system/timeout error
     */
    public function isSystemError(): bool
    {
        return str_starts_with($this->value, '9');
    }

    /**
     * Try to create from string code
     */
    public static function tryFrom(string $code): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $code) {
                return $case;
            }
        }

        return null;
    }
}
