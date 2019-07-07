<?php

declare(strict_types=1);

namespace App;

class Http
{
    public static function badRequest(): void
    {
        if (! headers_sent()) {
            header('HTTP/1.1 400 Bad request');
        }

        die('You have to provide invoiceId in query.');
    }

    public static function notFound(): void
    {
        if (! headers_sent()) {
            header('HTTP/1.1 404 Not found');
        }

        die('Invoice not found.');
    }

    public static function forbidden(): void
    {
        if (! headers_sent()) {
            header('HTTP/1.1 403 Forbidden');
        }

        die('You\'re not allowed to access this page.');
    }
}
