<?php

namespace ArionPay\Laravel\Console;

use ArionPay\Laravel\ArionPay;
use Illuminate\Console\Command;

class TestWebhookCommand extends Command
{
    protected $signature = 'arionpay:test-webhook {event=invoice.paid : invoice.paid, invoice.pending, invoice.partial, invoice.expired or invoice.needs_review}';

    protected $description = "Ask ArionPay to send a signed sample event to your store's webhook address";

    public function handle(ArionPay $arionpay): int
    {
        try {
            $r = $arionpay->sendTestWebhook((string) $this->argument('event'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $ok = !empty($r['delivered']);
        $this->{$ok ? 'info' : 'error'}(($ok ? 'Delivered' : 'Not delivered') . (isset($r['httpStatus']) ? ' (HTTP ' . $r['httpStatus'] . ')' : '') . (isset($r['url']) ? ' to ' . $r['url'] : ''));
        if (!$ok) {
            $this->line("Set your store's webhook address in the ArionPay dashboard (Webhooks) to " . $arionpay->webhookUrl());
        }
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
