<?php

declare(strict_types=1);

namespace FioCz\Data;

class PluginData extends UcrmData
{
    public ?string $lastProcessedPayment = null;

    public ?string $paymentMatchAttribute = null;

    public ?string $startDate = null;

    public ?string $token = null;

    public ?string $lastProcessedTimestamp = null;

    public ?string $importUnattached = null;

    public ?string $lastProcessedPaymentDateTime = null;
}
