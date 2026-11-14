<?php

namespace ArionPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array createInvoice(array $params)
 * @method static array getInvoice(string $invoiceId)
 * @method static array simulatePayment(string $invoiceId, string $scenario = 'exact')
 * @method static array sendTestWebhook(string $eventType = 'invoice.paid')
 * @method static array currencies()
 * @method static array rates(string $currency = 'USD')
 * @method static string webhookUrl()
 * @method static bool isTestMode()
 * @method static \ArionPay\Client client()
 *
 * @see \ArionPay\Laravel\ArionPay
 */
class ArionPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ArionPay\Laravel\ArionPay::class;
    }
}
