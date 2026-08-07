<?php

declare(strict_types=1);

namespace BackupSyncGoogleDrive\Utility;

use BackupSyncGoogleDrive\Service\UnmsApiGoogleDrive;
use DateTimeZone;
use Exception;

final class NmsSettings
{
    /**
     * @var UnmsApiGoogleDrive
     */
    private $unmsApiGoogleDrive;

    public function __construct(UnmsApiGoogleDrive $unmsApiGoogleDrive)
    {
        $this->unmsApiGoogleDrive = $unmsApiGoogleDrive;
    }

    public function getTimeZone(): DateTimeZone
    {
        try {
            $nmsSettings = $this->unmsApiGoogleDrive->get('nms/settings');
            $timeZone = is_array($nmsSettings)
                ? ($nmsSettings['timezone'] ?? null)
                : null;

            if (! is_string($timeZone) || $timeZone === '') {
                return new DateTimeZone(date_default_timezone_get());
            }

            return new DateTimeZone($timeZone);
        } catch (Exception $exception) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }
}
