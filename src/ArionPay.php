<?php

namespace ArionPay\Laravel;

use ArionPay\Client;

/**
 * The ArionPay client with your Laravel settings filled in: new invoices are test invoices in test mode, and send
 * their payment updates to this app's webhook route unless you pass another callbackUrl.
 *
 * Every other method of \ArionPay\Client (getInvoice, simulatePayment, sendTestWebhook, currencies, rates) works too.
 *
 * @mixin Client
 */
class ArionPay
{
    public function __construct(private Client $client, private array $config)
    {
    }

    /**
     * Create an invoice. Returns id, invoiceUrl (send the customer there), status, expiresAt, ...
     *
     * Pass your own idempotencyKey (for example "order-1001") so a retried request never makes a second invoice.
     *
     * @param array<string,mixed> $params amount (required), currency, orderId, description, successUrl, cancelUrl,
     *                                    callbackUrl, asset, allowedAssets, expiresMin, metadata, idempotencyKey, test
     */
    public function createInvoice(array $params): array
    {
        if (!array_key_exists('test', $params)) {
            $params['test'] = (bool) ($this->config['test_mode'] ?? true);
        }
        if (!array_key_exists('callbackUrl', $params) && ($this->config['webhook']['enabled'] ?? true)) {
            $params['callbackUrl'] = $this->webhookUrl();
        }
        return $this->client->createInvoice($params);
    }

    /** The full address of this app's ArionPay webhook route. */
    public function webhookUrl(): string
    {
        return url($this->config['webhook']['path'] ?? 'arionpay/webhook');
    }

    public function isTestMode(): bool
    {
        return (bool) ($this->config['test_mode'] ?? true);
    }

    /** The underlying \ArionPay\Client. */
    public function client(): Client
    {
        return $this->client;
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->client->{$method}(...$arguments);
    }
}
