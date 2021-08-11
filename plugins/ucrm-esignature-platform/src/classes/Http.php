<?php

declare(strict_types=1);

namespace App;

class Http
{
    public static function forbidden(): void
    {
        if (! headers_sent()) {
            header("HTTP/1.1 403 Forbidden");
        }

        die('<b>Nu ai permisiunea sa accesezi aceasta pagina!</b>');
    }
}
