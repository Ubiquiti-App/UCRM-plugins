<?php

declare(strict_types=1);

namespace UBarcode\Service;

use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

final class LogCleaner
{
    private const MAX_LINES = 1024;

    public function __construct(private PluginLogManager $pluginLogManager)
    {
    }

    public function clean(): void
    {
        $log = $this->pluginLogManager->getLog();
        $log = explode(PHP_EOL, $log);

        if (count($log) <= self::MAX_LINES) {
            return;
        }

        $log = array_slice($log, -self::MAX_LINES);
        $this->pluginLogManager->clearLog();
        $this->pluginLogManager->appendLog(rtrim(implode(PHP_EOL, $log), PHP_EOL));
    }
}
