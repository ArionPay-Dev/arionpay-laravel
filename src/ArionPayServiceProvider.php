<?php

namespace ArionPay\Laravel;

use ArionPay\Client;
use ArionPay\Laravel\Console\CheckCommand;
use ArionPay\Laravel\Console\TestWebhookCommand;
use ArionPay\Laravel\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ArionPayServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.0';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arionpay.php', 'arionpay');

        $this->app->singleton(Client::class, function ($app) {
            $c = $app['config']['arionpay'];
            return new Client((string) $c['api_key'], (string) $c['api_secret'], [
                'webhookSecret' => (string) ($c['webhook_secret'] ?? ''),
                'baseUrl' => (string) ($c['base_url'] ?: Client::DEFAULT_BASE_URL),
                'appInfo' => 'laravel/' . $app->version() . ' arionpay-laravel/' . self::VERSION,
            ]);
        });
        $this->app->singleton(ArionPay::class, fn ($app) => new ArionPay($app->make(Client::class), $app['config']['arionpay']));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/arionpay.php' => config_path('arionpay.php')], 'arionpay-config');
            $this->commands([CheckCommand::class, TestWebhookCommand::class]);
        }

        if (config('arionpay.webhook.enabled', true) && !$this->app->routesAreCached()) {
            // Outside the "web" group on purpose: no session, no CSRF. The request is trusted only by its signature.
            Route::post(config('arionpay.webhook.path', 'arionpay/webhook'), WebhookController::class)
                ->middleware((array) config('arionpay.webhook.middleware', []))
                ->name('arionpay.webhook');
        }
    }
}
