<?php

namespace Kaninstein\LaravelPagarme\Facades;

use Illuminate\Support\Facades\Facade;
use Kaninstein\LaravelPagarme\Services\ChargeService;
use Kaninstein\LaravelPagarme\Services\CustomerService;
use Kaninstein\LaravelPagarme\Services\OrderService;
use Kaninstein\LaravelPagarme\Services\WebhookService;
use Kaninstein\LaravelPagarme\Services\CardService;
use Kaninstein\LaravelPagarme\Services\TokenService;
use Kaninstein\LaravelPagarme\Services\BinService;
use Kaninstein\LaravelPagarme\Services\FeeCalculatorService;

/**
 * @method static CustomerService customers()
 * @method static OrderService orders()
 * @method static ChargeService charges()
 * @method static WebhookService webhooks()
 * @method static CardService cards()
 * @method static TokenService tokens()
 * @method static BinService bin()
 * @method static FeeCalculatorService feeCalculator()
 * @method static array get(string $endpoint, array $query = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array patch(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint)
 *
 * @see \Kaninstein\LaravelPagarme\Client\PagarmeClient
 */
class Pagarme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pagarme';
    }

    /**
     * Get CustomerService instance
     */
    public static function customers(): CustomerService
    {
        return app(CustomerService::class);
    }

    /**
     * Get OrderService instance
     */
    public static function orders(): OrderService
    {
        return app(OrderService::class);
    }

    /**
     * Get ChargeService instance
     */
    public static function charges(): ChargeService
    {
        return app(ChargeService::class);
    }

    /**
     * Get WebhookService instance
     */
    public static function webhooks(): WebhookService
    {
        return app(WebhookService::class);
    }

    /**
     * Get CardService instance
     */
    public static function cards(): CardService
    {
        return app(CardService::class);
    }

    /**
     * Get TokenService instance
     */
    public static function tokens(): TokenService
    {
        return app(TokenService::class);
    }

    /**
     * Get BinService instance
     */
    public static function bin(): BinService
    {
        return app(BinService::class);
    }

    /**
     * Get FeeCalculatorService instance
     */
    public static function feeCalculator(): FeeCalculatorService
    {
        return app(FeeCalculatorService::class);
    }
}
