<?php

namespace ArionPay\Laravel\Console;

use ArionPay\ApiException;
use ArionPay\Laravel\ArionPay;
use Illuminate\Console\Command;

class CheckCommand extends Command
{
    protected $signature = 'arionpay:check';

    protected $description = 'Check the ArionPay keys and show the webhook address';

    public function handle(ArionPay $arionpay): int
    {
        $this->line('Mode: ' . ($arionpay->isTestMode() ? 'TEST (no real money)' : 'LIVE'));
        $this->line('Webhook address: ' . $arionpay->webhookUrl());
        if ((string) config('arionpay.webhook_secret') === '') {
            $this->warn('ARIONPAY_WEBHOOK_SECRET is empty: webhooks will be refused.');
        }
        try {
            // A signed read of an invoice that cannot exist: "not found" means the keys were accepted.
            $arionpay->getInvoice('000000000000000000000000');
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->info('API key and secret: OK');
                return self::SUCCESS;
            }
            $this->error('ArionPay refused the keys: ' . $e->getMessage());
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info('API key and secret: OK');
        return self::SUCCESS;
    }
}
