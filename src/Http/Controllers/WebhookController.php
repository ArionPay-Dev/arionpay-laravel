<?php

namespace ArionPay\Laravel\Http\Controllers;

use ArionPay\Laravel\Events;
use ArionPay\SignatureException;
use ArionPay\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * POST /arionpay/webhook: checks the signature over the exact body, refuses test invoices in live mode (and live ones
 * in test mode), handles each event once, and fires one Laravel event. When a listener throws, the answer is 500 and
 * ArionPay delivers again later.
 */
class WebhookController
{
    private const MAX_BYTES = 262144;

    private const EVENTS = [
        'invoice.paid' => Events\InvoicePaid::class,
        'invoice.pending' => Events\InvoicePending::class,
        'invoice.partial' => Events\InvoicePartial::class,
        'invoice.expired' => Events\InvoiceExpired::class,
        'invoice.needs_review' => Events\InvoiceNeedsReview::class,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('arionpay.webhook_secret', '');
        if ($secret === '') {
            Log::error('ArionPay webhook refused: ARIONPAY_WEBHOOK_SECRET is not set.');
            return response()->json(['error' => 'webhook secret not set'], 503);
        }
        $raw = $request->getContent();
        if (strlen($raw) > self::MAX_BYTES) {
            return response()->json(['error' => 'too large'], 413);
        }
        try {
            $event = Webhook::verify($raw, $request->header('ArionPay-Signature'), $secret);
        } catch (SignatureException $e) {
            Log::warning('ArionPay webhook refused: ' . $e->getMessage());
            return response()->json(['error' => 'invalid signature'], 401);
        }

        if (Webhook::isTestEvent($event)) {
            Event::dispatch(new Events\TestWebhookReceived($event));
            return response()->json(['received' => true, 'test' => true]);
        }

        $test = (bool) ($event['data']['invoice']['test'] ?? false);
        if ($test !== (bool) config('arionpay.test_mode', true)) {
            Log::warning(sprintf('ArionPay webhook %s refused: %s invoice while ARIONPAY_TEST_MODE is %s.', $event['id'], $test ? 'test' : 'live', $test ? 'false' : 'true'));
            return response()->json(['error' => 'test/live mode mismatch'], 409);
        }

        $cache = Cache::store(config('arionpay.webhook.cache_store'));
        $key = 'arionpay:event:' . $event['id'];
        if (!$cache->add($key, 1, now()->addHours((int) config('arionpay.webhook.dedupe_hours', 96)))) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        $class = self::EVENTS[$event['type']] ?? Events\UnknownWebhookReceived::class;
        try {
            Event::dispatch(new $class($event));
        } catch (\Throwable $e) {
            $cache->forget($key); // let ArionPay's next delivery try again
            report($e);
            return response()->json(['error' => 'listener failed'], 500);
        }
        return response()->json(['received' => true]);
    }
}
