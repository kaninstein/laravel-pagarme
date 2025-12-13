<?php

/**
 * BIN (Bank Identifier Number) Service Examples
 *
 * This file demonstrates how to use the BinService to:
 * - Look up card information by BIN (first 6 digits)
 * - Get brand, CVV length, and formatting information
 * - Validate card numbers
 * - Format card numbers with proper spacing
 * - Use caching for performance
 */

use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;
use Kaninstein\LaravelPagarme\Exceptions\NotFoundException;

/**
 * Example 1: Basic BIN Lookup
 * Get information about a card using its BIN (first 6 digits)
 */
function example1_basicBinLookup()
{
    try {
        // Look up a Visa BIN
        $binInfo = Pagarme::bin()->get('411111');

        echo "Brand: " . $binInfo->brand . "\n";
        echo "CVV Length: " . $binInfo->cvv . "\n";
        echo "Possible Lengths: " . implode(', ', $binInfo->lengths) . "\n";
        echo "Brand Display Name: " . $binInfo->getBrandDisplayName() . "\n";

        // Output:
        // Brand: visa
        // CVV Length: 3
        // Possible Lengths: 16
        // Brand Display Name: Visa

    } catch (NotFoundException $e) {
        echo "BIN not found: " . $e->getMessage();
    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 2: BIN Lookup from Full Card Number
 * Automatically extract BIN from a complete card number
 */
function example2_binFromCardNumber()
{
    try {
        // Full card number - BIN will be extracted automatically
        $binInfo = Pagarme::bin()->getFromCardNumber('4111 1111 1111 1111');

        echo "Detected Brand: " . $binInfo->getBrandDisplayName() . "\n";

        // Works with any format (spaces, dashes, or none)
        $binInfo2 = Pagarme::bin()->getFromCardNumber('4111-1111-1111-1111');
        $binInfo3 = Pagarme::bin()->getFromCardNumber('4111111111111111');

        // All return the same result

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 3: Get Brand from Card Number
 * Quick method to get just the brand
 */
function example3_getBrand()
{
    try {
        $brand = Pagarme::bin()->getBrand('5555 5555 5555 4444');
        echo "Brand: " . $brand . "\n"; // Output: mastercard

        $brand2 = Pagarme::bin()->getBrand('3782 822463 10005');
        echo "Brand: " . $brand2 . "\n"; // Output: amex

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 4: Get CVV Length
 * Different cards have different CVV lengths
 */
function example4_getCvvLength()
{
    try {
        // Most cards have 3-digit CVV
        $cvvLength = Pagarme::bin()->getCvvLength('4111111111111111');
        echo "Visa CVV Length: " . $cvvLength . "\n"; // 3

        // American Express has 4-digit CVV
        $cvvLength2 = Pagarme::bin()->getCvvLength('378282246310005');
        echo "Amex CVV Length: " . $cvvLength2 . "\n"; // 4

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 5: Validate Card Number Length
 * Check if a card number has the correct length for its brand
 */
function example5_validateCardLength()
{
    try {
        // Valid Visa card (16 digits)
        $isValid = Pagarme::bin()->isValidCardLength('4111111111111111');
        echo "Valid Visa: " . ($isValid ? 'Yes' : 'No') . "\n"; // Yes

        // Invalid - too short
        $isValid2 = Pagarme::bin()->isValidCardLength('411111111111');
        echo "Valid (too short): " . ($isValid2 ? 'Yes' : 'No') . "\n"; // No

        // American Express (15 digits is valid)
        $isValid3 = Pagarme::bin()->isValidCardLength('378282246310005');
        echo "Valid Amex: " . ($isValid3 ? 'Yes' : 'No') . "\n"; // Yes

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 6: Format Card Number with Proper Spacing
 * Each brand has specific formatting requirements
 */
function example6_formatCardNumber()
{
    try {
        // Visa - 4 groups of 4 digits
        $formatted = Pagarme::bin()->formatCardNumber('4111111111111111');
        echo "Formatted Visa: " . $formatted . "\n"; // 4111 1111 1111 1111

        // American Express - 4-6-5 format
        $formatted2 = Pagarme::bin()->formatCardNumber('378282246310005');
        echo "Formatted Amex: " . $formatted2 . "\n"; // 3782 822463 10005

        // Works with already formatted input
        $formatted3 = Pagarme::bin()->formatCardNumber('5555-5555-5555-4444');
        echo "Formatted Mastercard: " . $formatted3 . "\n"; // 5555 5555 5555 4444

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 7: Check if BIN Exists
 * Verify if a BIN is valid before processing
 */
function example7_checkBinExists()
{
    $binService = Pagarme::bin();

    // Check valid BIN
    $exists = $binService->exists('411111');
    echo "BIN 411111 exists: " . ($exists ? 'Yes' : 'No') . "\n";

    // Check invalid BIN
    $exists2 = $binService->exists('000000');
    echo "BIN 000000 exists: " . ($exists2 ? 'Yes' : 'No') . "\n";
}

/**
 * Example 8: Working with Cache
 * BIN information is cached to improve performance
 */
function example8_cacheUsage()
{
    try {
        $binService = Pagarme::bin();

        // First call - fetches from API and caches
        $start = microtime(true);
        $binInfo1 = $binService->get('411111');
        $time1 = (microtime(true) - $start) * 1000;
        echo "First call (API): " . round($time1, 2) . "ms\n";

        // Second call - returns from cache (much faster)
        $start = microtime(true);
        $binInfo2 = $binService->get('411111');
        $time2 = (microtime(true) - $start) * 1000;
        echo "Second call (Cache): " . round($time2, 2) . "ms\n";

        // Bypass cache if needed
        $binInfo3 = $binService->get('411111', useCache: false);
        echo "Cache bypassed - fresh data from API\n";

        // Clear specific BIN from cache
        $binService->clearCache('411111');
        echo "Cache cleared for BIN 411111\n";

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 9: Multiple Possible Brands
 * Some BINs can match multiple brands
 */
function example9_multipleBrands()
{
    try {
        $binInfo = Pagarme::bin()->get('411111');

        echo "Primary Brand: " . $binInfo->brand . "\n";

        if ($binInfo->hasMultipleBrands()) {
            echo "Possible Brands: " . implode(', ', $binInfo->possibleBrands) . "\n";
        } else {
            echo "Single brand only\n";
        }

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 10: Get All BIN Information
 * Access complete BIN data including gaps, mask, and images
 */
function example10_completeBinInfo()
{
    try {
        $binInfo = Pagarme::bin()->get('411111');

        echo "=== Complete BIN Information ===\n";
        echo "Brand: " . $binInfo->brand . "\n";
        echo "Display Name: " . $binInfo->getBrandDisplayName() . "\n";
        echo "CVV Length: " . $binInfo->cvv . "\n";
        echo "Card Lengths: " . implode(', ', $binInfo->lengths) . "\n";
        echo "Gaps (spacing): " . implode(', ', $binInfo->gaps) . "\n";
        echo "Mask: " . $binInfo->mask . "\n";
        echo "Brand Image URL: " . $binInfo->brandImage . "\n";
        echo "Possible Brands: " . implode(', ', $binInfo->possibleBrands) . "\n";

        // Convert to array for storage or API response
        $data = $binInfo->toArray();

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Example 11: Common Brazilian Card Brands
 * Examples with popular Brazilian cards
 */
function example11_brazilianCards()
{
    $cards = [
        '411111' => 'Visa',
        '555555' => 'Mastercard',
        '636368' => 'Elo',
        '606282' => 'Hipercard',
        '378282' => 'American Express',
        '506726' => 'Aura',
    ];

    foreach ($cards as $bin => $expectedBrand) {
        try {
            $binInfo = Pagarme::bin()->get($bin);
            echo sprintf(
                "%s: %s (CVV: %d digits)\n",
                $expectedBrand,
                $binInfo->getBrandDisplayName(),
                $binInfo->cvv
            );
        } catch (PagarmeException $e) {
            echo "$expectedBrand: Error - " . $e->getMessage() . "\n";
        }
    }
}

/**
 * Example 12: Form Validation Helper
 * Use BIN service for real-time card validation in forms
 */
function example12_formValidation()
{
    $cardNumber = '4111 1111 1111 1111';
    $cvv = '123';

    try {
        $binService = Pagarme::bin();
        $binInfo = $binService->getFromCardNumber($cardNumber);

        // Validate card length
        if (!$binService->isValidCardLength($cardNumber)) {
            echo "Error: Invalid card number length\n";
            return false;
        }

        // Validate CVV length
        if (strlen($cvv) !== $binInfo->cvv) {
            echo "Error: CVV should be {$binInfo->cvv} digits for {$binInfo->getBrandDisplayName()}\n";
            return false;
        }

        // Get formatted number for display
        $formatted = $binService->formatCardNumber($cardNumber);

        echo "✓ Valid {$binInfo->getBrandDisplayName()} card\n";
        echo "✓ Formatted: $formatted\n";

        return true;

    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Example 13: Error Handling
 * Handle various error scenarios
 */
function example13_errorHandling()
{
    $binService = Pagarme::bin();

    // Invalid BIN format (not 6 digits)
    try {
        $binService->get('1234'); // Too short
    } catch (PagarmeException $e) {
        echo "Format Error: " . $e->getMessage() . "\n";
        // Output: BIN must be exactly 6 digits
    }

    // BIN not found
    try {
        $binService->get('000000'); // Invalid BIN
    } catch (NotFoundException $e) {
        echo "Not Found: " . $e->getMessage() . "\n";
        // Output: BIN not found: 000000
    } catch (PagarmeException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    // Network or API errors
    try {
        $binService->get('411111');
    } catch (PagarmeException $e) {
        echo "API Error: " . $e->getMessage() . "\n";
        echo "Status Code: " . $e->getCode() . "\n";
    }
}

/**
 * Example 14: Practical Use Case - Card Input Component
 * Real-world scenario for a payment form
 */
function example14_cardInputComponent()
{
    // Simulating user typing a card number
    $cardNumber = '4111111111111111';

    // Extract BIN (first 6 digits)
    $bin = substr(preg_replace('/\D/', '', $cardNumber), 0, 6);

    if (strlen($bin) === 6) {
        try {
            $binInfo = Pagarme::bin()->get($bin);

            // Update UI based on BIN info
            $response = [
                'brand' => $binInfo->brand,
                'brandName' => $binInfo->getBrandDisplayName(),
                'brandImage' => $binInfo->brandImage,
                'cvvLength' => $binInfo->cvv,
                'maxLength' => $binInfo->getCardLength(),
                'formatted' => $binInfo->formatCardNumber($cardNumber),
                'isValid' => Pagarme::bin()->isValidCardLength($cardNumber),
            ];

            echo json_encode($response, JSON_PRETTY_PRINT) . "\n";

            /*
            {
                "brand": "visa",
                "brandName": "Visa",
                "brandImage": "https://...",
                "cvvLength": 3,
                "maxLength": 16,
                "formatted": "4111 1111 1111 1111",
                "isValid": true
            }
            */

        } catch (NotFoundException $e) {
            echo json_encode(['error' => 'Unknown card brand']) . "\n";
        }
    }
}

// Run examples
echo "=== Example 1: Basic BIN Lookup ===\n";
example1_basicBinLookup();
echo "\n";

echo "=== Example 2: BIN from Card Number ===\n";
example2_binFromCardNumber();
echo "\n";

echo "=== Example 3: Get Brand ===\n";
example3_getBrand();
echo "\n";

echo "=== Example 4: Get CVV Length ===\n";
example4_getCvvLength();
echo "\n";

echo "=== Example 5: Validate Card Length ===\n";
example5_validateCardLength();
echo "\n";

echo "=== Example 6: Format Card Number ===\n";
example6_formatCardNumber();
echo "\n";

echo "=== Example 7: Check BIN Exists ===\n";
example7_checkBinExists();
echo "\n";

echo "=== Example 8: Cache Usage ===\n";
example8_cacheUsage();
echo "\n";

echo "=== Example 9: Multiple Brands ===\n";
example9_multipleBrands();
echo "\n";

echo "=== Example 10: Complete BIN Info ===\n";
example10_completeBinInfo();
echo "\n";

echo "=== Example 11: Brazilian Cards ===\n";
example11_brazilianCards();
echo "\n";

echo "=== Example 12: Form Validation ===\n";
example12_formValidation();
echo "\n";

echo "=== Example 13: Error Handling ===\n";
example13_errorHandling();
echo "\n";

echo "=== Example 14: Card Input Component ===\n";
example14_cardInputComponent();
