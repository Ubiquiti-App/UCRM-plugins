<?php

declare(strict_types=1);

namespace MikrotikQueueSync;

class Http
{
    public static function forbidden(): void
    {
        if (! headers_sent()) {
            header('HTTP/1.1 403 Forbidden');
        }

        die('You\'re not allowed to access this page.');
    }
}
