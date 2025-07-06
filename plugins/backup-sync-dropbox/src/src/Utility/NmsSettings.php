<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Utility;

use BackupSyncDropbox\Service\UnmsApiDropbox;
use DateTimeZone;
use Exception;

final class NmsSettings
{
    public function __construct(private UnmsApiDropbox $unmsApiDropbox)
    {
    }

    public function getTimeZone(): DateTimeZone
    {
        try {
            $nmsSettings = $this->unmsApiDropbox->get('nms/settings');
            $timeZone = is_array($nmsSettings)
                ? ($nmsSettings['timezone'] ?? null)
                : null;

            return new DateTimeZone($timeZone);
        } catch (Exception $exception) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }
}
