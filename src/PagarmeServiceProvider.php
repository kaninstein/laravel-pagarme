<?php

namespace Kaninstein\LaravelPagarme;

use Illuminate\Support\ServiceProvider;
use Kaninstein\LaravelPagarme\Client\PagarmeClient;
use Kaninstein\LaravelPagarme\Services\CustomerService;
use Kaninstein\LaravelPagarme\Services\OrderService;
use Kaninstein\LaravelPagarme\Services\ChargeService;
use Kaninstein\LaravelPagarme\Services\WebhookService;
use Kaninstein\LaravelPagarme\Services\CardService;
use Kaninstein\LaravelPagarme\Services\TokenService;
use Kaninstein\LaravelPagarme\Services\BinService;

class PagarmeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pagarme.php', 'pagarme'
        );

        // Register the main client
        $this->app->singleton(PagarmeClient::class, function ($app) {
            return new PagarmeClient(
                secretKey: config('pagarme.secret_key'),
                apiUrl: config('pagarme.api_url'),
                timeout: config('pagarme.timeout')
            );
        });

        // Register services
        $this->app->singleton(CustomerService::class, function ($app) {
            return new CustomerService($app->make(PagarmeClient::class));
        });

        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService($app->make(PagarmeClient::class));
        });

        $this->app->singleton(ChargeService::class, function ($app) {
            return new ChargeService($app->make(PagarmeClient::class));
        });

        $this->app->singleton(WebhookService::class, function ($app) {
            return new WebhookService($app->make(PagarmeClient::class));
        });

        $this->app->singleton(CardService::class, function ($app) {
            return new CardService($app->make(PagarmeClient::class));
        });

        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService();
        });

        $this->app->singleton(BinService::class, function ($app) {
            return new BinService();
        });

        // Alias for easy access
        $this->app->alias(PagarmeClient::class, 'pagarme');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/pagarme.php' => config_path('pagarme.php'),
        ], 'pagarme-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\SetupWebhooksCommand::class,
            ]);
        }
    }
}
