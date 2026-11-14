<?php

namespace ArionPay\Laravel\Events;

/** Time to pay ran out without full payment. A late payment can still follow as InvoicePaid. */
class InvoiceExpired extends ArionPayEvent
{
}
