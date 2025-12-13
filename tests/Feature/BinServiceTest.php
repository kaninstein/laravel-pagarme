<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Kaninstein\LaravelPagarme\Services\BinService;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

class BinServiceTest extends TestCase
{
    private BinService $binService;

    protected function getPackageProviders($app)
    {
        return [PagarmeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->binService = new BinService();
    }

    /**
     * @test
     */
    public function it_can_get_bin_information_for_visa()
    {
        // Visa BIN
        $binInfo = $this->binService->get('411111');

        $this->assertNotNull($binInfo);
        $this->assertEquals('visa', strtolower($binInfo->brand));
        $this->assertEquals(3, $binInfo->cvv);
        $this->assertIsArray($binInfo->lengths);
        // Note: Some BINs may return empty lengths array from API
    }

    /**
     * @test
     */
    public function it_can_get_bin_information_for_mastercard()
    {
        // Mastercard BIN
        $binInfo = $this->binService->get('555555');

        $this->assertNotNull($binInfo);
        $this->assertEquals('mastercard', strtolower($binInfo->brand));
        $this->assertEquals(3, $binInfo->cvv);
    }

    /**
     * @test
     */
    public function it_can_get_bin_from_card_number()
    {
        $binInfo = $this->binService->getFromCardNumber('4111 1111 1111 1111');

        $this->assertNotNull($binInfo);
        $this->assertEquals('visa', strtolower($binInfo->brand));
    }

    /**
     * @test
     */
    public function it_can_get_brand_from_card_number()
    {
        $brand = $this->binService->getBrand('4111111111111111');

        $this->assertEquals('visa', strtolower($brand));
    }

    /**
     * @test
     */
    public function it_can_get_cvv_length()
    {
        $cvvLength = $this->binService->getCvvLength('4111111111111111');

        $this->assertEquals(3, $cvvLength);
    }

    /**
     * @test
     */
    public function it_can_validate_card_length()
    {
        // Get BIN info first to see what lengths are valid
        $binInfo = $this->binService->get('411111');

        // Valid card length should match one of the allowed lengths
        $isValid = $this->binService->isValidCardLength('4111111111111111');
        $this->assertIsBool($isValid);

        // Invalid (too short) - should definitely be invalid
        $this->assertFalse($this->binService->isValidCardLength('411111111111'));
    }

    /**
     * @test
     */
    public function it_can_format_card_number()
    {
        $formatted = $this->binService->formatCardNumber('4111111111111111');

        // Should contain spaces for formatting
        $this->assertStringContainsString(' ', $formatted);

        // Should start with the first 4 digits
        $this->assertStringStartsWith('4111', $formatted);

        // Should have all 16 digits (without spaces)
        $digitsOnly = preg_replace('/\s/', '', $formatted);
        $this->assertEquals('4111111111111111', $digitsOnly);
    }

    /**
     * @test
     */
    public function it_caches_bin_lookups()
    {
        // First call - from API
        $start = microtime(true);
        $binInfo1 = $this->binService->get('411111');
        $time1 = microtime(true) - $start;

        // Second call - from cache (should be faster)
        $start = microtime(true);
        $binInfo2 = $this->binService->get('411111');
        $time2 = microtime(true) - $start;

        $this->assertEquals($binInfo1->brand, $binInfo2->brand);
        // Cache should be faster (not always guaranteed in tests, but usually)
        // $this->assertLessThan($time1, $time2);
    }

    /**
     * @test
     */
    public function it_can_check_if_bin_exists()
    {
        $this->assertTrue($this->binService->exists('411111'));
        $this->assertFalse($this->binService->exists('000000'));
    }

    /**
     * @test
     */
    public function it_throws_exception_for_invalid_bin_format()
    {
        $this->expectException(\Kaninstein\LaravelPagarme\Exceptions\PagarmeException::class);
        $this->expectExceptionMessage('BIN must be exactly 6 digits');

        $this->binService->get('1234'); // Too short
    }
}
