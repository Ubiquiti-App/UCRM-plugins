<?php
/*
 * @copyright Copyright (c) 2021 Ubiquiti Inc.
 * @see https://www.ui.com/
 */

declare(strict_types=1);

namespace BackupSyncDropbox\Utility;

final class Strings
{
    public static function trimNonEmpty($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return trim($value);
    }
}
