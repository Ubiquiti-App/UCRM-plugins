<?php

declare(strict_types=1);

namespace FioCz\Data;

class PluginData extends UcrmData
{
    /**
     * @var string
     */
    public $lastProcessedPayment;

    /**
     * @var string
     */
    public $paymentMatchAttribute;

    /**
     * @var string
     */
    public $startDate;

    /**
     * @var string
     */
    public $token;
}
