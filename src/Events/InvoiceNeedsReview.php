<?php

namespace ArionPay\Laravel\Events;

/** Payment Radar: the payment was not exact. Decide in your ArionPay dashboard; if you accept it, InvoicePaid follows. */
class InvoiceNeedsReview extends ArionPayEvent
{
}
