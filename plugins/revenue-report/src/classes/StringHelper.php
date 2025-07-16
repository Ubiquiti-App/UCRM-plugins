<?php

declare(strict_types=1);


namespace App;

final class StringHelper
{
    public static function trimNonEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
