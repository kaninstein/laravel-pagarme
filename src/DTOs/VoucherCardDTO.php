<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Voucher Card DTO - Alias for CreditCardDTO
 *
 * Vouchers (meal/food vouchers) use the same structure as credit cards
 * This is just a semantic alias for better code readability
 *
 * Common voucher brands:
 * - Alelo
 * - Sodexo
 * - Ticket
 * - VR (Vale Refeição)
 */
class VoucherCardDTO extends CreditCardDTO
{
    // Inherits all methods from CreditCardDTO
}
