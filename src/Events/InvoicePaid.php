<?php

namespace ArionPay\Laravel\Events;

/** Fully paid and confirmed: deliver now (after checking matches() against your order). Also sent for late payments, payments inside your payment tolerance and payments you accepted in Payment Radar. */
class InvoicePaid extends ArionPayEvent
{
}
