<?php

declare(strict_types=1);


namespace SmsNotifier\Service;

use Psr\Log\LogLevel;

class Logger extends \Katzgrau\KLogger\Logger
{
    public function __construct()
    {
        parent::__construct(
            'data',
            LogLevel::INFO, // set to DEBUG for more verbosity
            [
                'extension' => 'log',
                'filename' => 'plugin',
            ]
        );
    }

    public function log($level, $message, array $context = array())
    {
        if (!is_string($message)) {
            $message = var_export($message,true);
        }
        return parent::log($level, $message, $context);
    }


}
