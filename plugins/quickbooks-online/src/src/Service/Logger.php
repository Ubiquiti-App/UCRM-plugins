<?php

declare(strict_types=1);


namespace QBExport\Service;

use Psr\Log\LogLevel;

class Logger extends \Katzgrau\KLogger\Logger
{
    const logFileDirectory = "data";
    const logFileName = "plugin";
    const logFileExtension = "log";
    public function __construct(?OptionsManager $optionsManager)
    {
        $logLevel = null;
        if ($optionsManager) {
            $pluginData = $optionsManager->load();
            $logLevel = Logger::checkLogLevel($pluginData->logLevel);
        }
        parent::__construct(
            self::logFileDirectory,
            $logLevel ?? LogLevel::INFO,
            [
                'extension' => self::logFileExtension,
                'filename' => self::logFileName,
            ]
        );
    }

    private function checkLogLevel(?string $level): ?string {
        if (!$level || ($trimmed = trim($level)) == '') return null;
        
        switch ($trimmed) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
            case LogLevel::WARNING:
            case LogLevel::NOTICE:
            case LogLevel::INFO:
            case LogLevel::DEBUG:
                return $trimmed;
        }

        return null;
    }
}
