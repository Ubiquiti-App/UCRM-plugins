<?php

declare(strict_types=1);

namespace SampleLogger;

class Logger
{
    private const LOG_FILE_PATH = 'data/plugin.log';

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
}
