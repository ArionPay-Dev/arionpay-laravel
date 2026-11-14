<?php

namespace ArionPay\Laravel\Events;

/**
 * A verified ArionPay webhook. $event is the whole event, $invoice its data.invoice:
 * id, orderId, status, amountFiat, currency, asset, network, paidCrypto, late, overpaid, underpaidWithinTolerance,
 * needsReview, reviewReason, manual, test, metadata, transactions, ...
 */
abstract class ArionPayEvent
{
    /** @var array<string,mixed> */
    public array $invoice;

    /** @param array<string,mixed> $event */
    public function __construct(public array $event)
    {
        $this->invoice = (array) ($event['data']['invoice'] ?? []);
    }

    public function eventId(): string
    {
        return (string) ($this->event['id'] ?? '');
    }

    public function type(): string
    {
        return (string) ($this->event['type'] ?? '');
    }

    public function invoiceId(): string
    {
        return (string) ($this->invoice['id'] ?? '');
    }

    /** Your own order number, as you passed it to createInvoice(). */
    public function orderId(): ?string
    {
        return isset($this->invoice['orderId']) ? (string) $this->invoice['orderId'] : null;
    }

    /** @return array<string,mixed> what you passed as metadata to createInvoice() */
    public function metadata(): array
    {
        return (array) ($this->invoice['metadata'] ?? []);
    }

    /** The invoice price in its currency (compare it with your order before you deliver). */
    public function amount(): string
    {
        return number_format((float) ($this->invoice['amountFiat'] ?? 0), 2, '.', '');
    }

    public function currency(): string
    {
        return strtoupper((string) ($this->invoice['currency'] ?? ''));
    }

    public function isTestInvoice(): bool
    {
        return (bool) ($this->invoice['test'] ?? false);
    }

    public function isLate(): bool
    {
        return (bool) ($this->invoice['late'] ?? false);
    }

    /** True when the order is exactly this amount and currency: the check to make before delivering. */
    public function matches(string|int|float $amount, string $currency): bool
    {
        return $this->amount() === number_format((float) $amount, 2, '.', '') && $this->currency() === strtoupper($currency);
    }
}
