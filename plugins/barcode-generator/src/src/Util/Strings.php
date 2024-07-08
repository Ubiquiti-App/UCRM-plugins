<?php

declare(strict_types=1);

namespace UBarcode\Util;

final class Strings
{
    public static function generateUuidWithDashes(): string
    {
        $uuidBin = random_bytes(18);
        $uuidBin &= "\xFF\xFF\xFF\xFF\x0F\xFF\xF0\x0F\xFF\x03\xFF\xF0\xFF\xFF\xFF\xFF\xFF\xFF";
        $uuidBin |= "\x00\x00\x00\x00\x00\x00\x00\x40\x00\x08\x00\x00\x00\x00\x00\x00\x00\x00";
        $uuidHex = bin2hex($uuidBin);
        $uuidHex[8] = $uuidHex[13] = $uuidHex[18] = $uuidHex[23] = '-';

        return $uuidHex;
    }
}
