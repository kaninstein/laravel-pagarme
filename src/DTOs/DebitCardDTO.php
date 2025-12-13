<?php

namespace Kaninstein\LaravelPagarme\DTOs;

/**
 * Debit Card DTO - Alias for CreditCardDTO
 *
 * Debit cards use the same structure as credit cards
 * This is just a semantic alias for better code readability
 */
class DebitCardDTO extends CreditCardDTO
{
    // Inherits all methods from CreditCardDTO
}
