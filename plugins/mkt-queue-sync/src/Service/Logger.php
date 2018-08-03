<?php

declare(strict_types=1);

namespace MikrotikQueueSync\Service;

use Psr\Log\LogLevel;

class Logger extends \Katzgrau\KLogger\Logger
{
    private $debugLogger;

    public function __construct($debug = false)
    {
        $this->debugLogger = (bool)$debug;
        parent::__construct(
            'data',
            LogLevel::DEBUG,
            [
                'extension' => 'log',
                'filename' => 'plugin',
            ]
        );
    }

    public function write($message)
    {
        if ($this->debugLogger) {
            echo $message, "\n";
        }
        parent::write($message);
    }
}
