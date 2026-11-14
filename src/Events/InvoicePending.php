<?php

namespace ArionPay\Laravel\Events;

/** A payment was seen on the network and waits for confirmations. Do not deliver yet. */
class InvoicePending extends ArionPayEvent
{
}
