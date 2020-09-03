<?php

declare(strict_types=1);

namespace QBExport\Service;

use Psr\Log\LogLevel;

class Logger extends \Katzgrau\KLogger\Logger
{
    public function __construct()
    {
        parent::__construct(
            'data',
            LogLevel::DEBUG,
            [
                'extension' => 'log',
                'filename' => 'plugin',
            ]
        );
    }
}
