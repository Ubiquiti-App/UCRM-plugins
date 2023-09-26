<?php

declare(strict_types=1);


namespace SmsNotifier\Service;

use Psr\Log\LogLevel;

class Logger extends \Katzgrau\KLogger\Logger
{
    private const DEFAULT_LEVEL = LogLevel::INFO; // now configurable in manifest

    private const AVAILABLE_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    public function __construct($level = null)
    {
        parent::__construct(
            'data',
            self::DEFAULT_LEVEL,
            [
                'extension' => 'log',
                'filename' => 'plugin',
            ]
        );
        if ($level) {
            $this->setLogLevelThreshold($level);
        }
    }

    /**
     * @param mixed $level
     * @param mixed $message
     */
    public function log($level, $message, array $context = [])
    {
        if (! is_string($message)) {
            $message = var_export($message, true);
        }
        return parent::log($level, $message, $context);
    }

    public function setLogLevelThreshold($logLevelThreshold): void
    {
        $logLevelThreshold = $this->validateLevel($logLevelThreshold, self::DEFAULT_LEVEL);
        parent::setLogLevelThreshold($logLevelThreshold);
        $this->notice('Logging level set to:' . $logLevelThreshold);
    }

    private function validateLevel($level, $defaultLevel): string
    {
        if (in_array($level, self::AVAILABLE_LEVELS, true)) {
            return $level;
        }
        return $defaultLevel;
    }
}
