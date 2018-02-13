<?php

declare(strict_types=1);

namespace SampleLogger;

class Logger
{
    private const LOG_FILE_PATH = 'data/plugin.log';
    private const CONFIG_FILE_PATH = 'data/config.json';

    public function log(string $message): void
    {
        file_put_contents(
            self::LOG_FILE_PATH,
            sprintf(
                '[%s] %s %s',
                (new \DateTimeImmutable())->format('Y-m-d G:i:s.u'),
                $message,
                PHP_EOL
            ),
            FILE_APPEND | LOCK_EX
        );
    }

    public function logOption()
    {
        if ($filecontent = file_get_contents(self::CONFIG_FILE_PATH)) {
            $decoded = json_decode($filecontent, true);
            $this->log(
                sprintf(
                    'Plugin parameter: %s',
                    $decoded['string'] ?? ''
                )
            );
        }
    }
}
