<?php

declare(strict_types=1);

namespace FioCz\Data;

class PluginData extends UcrmData
{
    public string $lastProcessedPayment;

    public string $paymentMatchAttribute;

    public string $startDate;

    public string $token;

    public string $lastProcessedTimestamp;

    public string $importUnattached;

    public string $lastProcessedPaymentDateTime;
}
