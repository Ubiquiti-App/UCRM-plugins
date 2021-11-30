<?php

declare(strict_types=1);


namespace QBExport\Service;

use Psr\Log\LogLevel;

class Logger extends \Katzgrau\KLogger\Logger
{
    public function __construct(?OptionsManager $optionsManager)
    {
        $logLevel = null;
        if ($optionsManager) {
            $pluginData = $optionsManager->load();
            $logLevel = Logger::checkLogLevel($pluginData->logLevel);
        }
        parent::__construct(
            'data',
            $logLevel ?? LogLevel::INFO,
            [
                'extension' => 'log',
                'filename' => 'plugin',
            ]
        );
    }

    private function checkLogLevel(?string $level): ?string {
        if (!$level) return null;
        
        switch (trim($level)) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
            case LogLevel::WARNING:
            case LogLevel::NOTICE:
            case LogLevel::INFO:
            case LogLevel::DEBUG:
                return trim($level);
        }

        return null;
    }
}
