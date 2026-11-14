# ArionPay for Laravel

Accept crypto payments (Bitcoin, USDT, Litecoin, Ethereum, TRON and more) in a Laravel app with [ArionPay](https://arionpay.com). The money goes straight to your own wallet: ArionPay never holds it.

- `ArionPay::createInvoice([...])` gives you the payment page address.
- Payment updates arrive as normal Laravel events (`InvoicePaid` and others). The signature check, the test/live check and duplicate deliveries are handled for you.
- `php artisan arionpay:check` and `php artisan arionpay:test-webhook` help you set it up.

For Laravel 10 to 13 with PHP 8.1 or newer. Tested on Laravel 13.42 and 12 against a real ArionPay server.

## Install

```bash
composer require arionpay/arionpay-laravel
```

Add to `.env`:

```
ARIONPAY_API_KEY=pk_...          # ArionPay dashboard > your store > API key
ARIONPAY_API_SECRET=sk_...       # ArionPay dashboard > your store > API secret
ARIONPAY_WEBHOOK_SECRET=whsec_...  # ArionPay dashboard > Webhooks > signing secret
ARIONPAY_TEST_MODE=true          # false for real payments
```

Check it: `php artisan arionpay:check`.

## Take a payment

```php
use ArionPay\Laravel\Facades\ArionPay;

$invoice = ArionPay::createInvoice([
    'amount' => $order->total,               // "49.90"
    'currency' => 'USD',
    'orderId' => (string) $order->id,
    'idempotencyKey' => 'order-' . $order->id, // the same order never gets a second invoice
    'successUrl' => route('orders.show', $order),
    'metadata' => ['order_id' => $order->id],
]);

return redirect($invoice['invoiceUrl']);
```

In test mode the invoice is a test invoice (no real money). Each invoice sends its updates to this app's webhook route (`POST /arionpay/webhook`) unless you pass your own `callbackUrl`. Coming back to `successUrl` does **not** prove payment: wait for `InvoicePaid`.

## Deliver when paid

```php
use ArionPay\Laravel\Events\InvoicePaid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

Event::listen(function (InvoicePaid $event) {
    DB::transaction(function () use ($event) {
        $order = Order::lockForUpdate()->find($event->metadata()['order_id'] ?? 0);
        if (!$order || $order->paid_at || !$event->matches($order->total, $order->currency)) {
            return; // unknown, already paid, or not the amount you asked for: check it by hand
        }
        $order->update(['paid_at' => now(), 'arionpay_invoice' => $event->invoiceId()]);
        // deliver...
    });
});
```

Events, all with `$event->invoice` (the invoice fields), `invoiceId()`, `orderId()`, `metadata()`, `amount()`, `currency()`, `isTestInvoice()`, `isLate()` and `matches($amount, $currency)`:

| Event | When |
| --- | --- |
| `InvoicePaid` | Fully paid and confirmed: deliver. Also for late payments, small differences inside your payment tolerance (default 1%), and payments you accepted in Payment Radar. |
| `InvoicePending` | Payment seen on the network, waiting for confirmations. Don't deliver yet. |
| `InvoicePartial` | Part of the amount was paid. |
| `InvoiceExpired` | Time ran out without full payment (a late payment can still follow as `InvoicePaid`). |
| `InvoiceNeedsReview` | Payment Radar: decide in your ArionPay dashboard; if you accept, `InvoicePaid` follows. |
| `TestWebhookReceived` | A sample from "Send test webhook". It describes a made-up invoice: never deliver for it. |

What the webhook route does for you:

- It checks the `ArionPay-Signature` over the exact body (5-minute window). Anything else gets `401` and no event fires.
- With `ARIONPAY_TEST_MODE=true` it accepts only test invoices; with `false`, only real ones. A mismatch gets `409`.
- Each event fires once (remembered for 96 hours in your cache).
- When a listener throws, the answer is `500` and ArionPay delivers again later. A queued listener (`ShouldQueue`) is retried by your queue instead.
- The route sits outside the `web` group, so it has no session or CSRF; the signature protects it. Change the path or add middleware in `config/arionpay.php` (`php artisan vendor:publish --tag=arionpay-config`).

You don't need to compare crypto amounts: ArionPay decides whether an invoice is paid.

## Other calls

```php
ArionPay::getInvoice($id);              // signed read, same fields as the webhook invoice
ArionPay::simulatePayment($id, 'exact'); // test invoices only
ArionPay::currencies();                 // coins you can pass as 'asset' / 'allowedAssets'
ArionPay::rates('EUR');
ArionPay::client();                     // the underlying \ArionPay\Client
```

To try a payment without real money, create a test invoice with `'asset' => 'USDT_TRC20'` and call `ArionPay::simulatePayment($invoice['id'], 'exact')`. You can also use the **Try a test payment** buttons in the ArionPay dashboard.

MIT licence.
