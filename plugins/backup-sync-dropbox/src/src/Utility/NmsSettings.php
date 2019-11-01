<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Utility;

use DateTimeZone;
use Exception;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

final class NmsSettings
{
    /**
     * @var UnmsApi
     */
    private $unmsApi;

    public function __construct(UnmsApi $unmsApi)
    {
        $this->unmsApi = $unmsApi;
    }

    public function getTimeZone(): DateTimeZone
    {
        try {
            $nmsSettings = $this->unmsApi->get('nms/settings');
            $timeZone = is_array($nmsSettings)
                ? ($nmsSettings['timezone'] ?? null)
                : null;

            return new DateTimeZone($timeZone);
        } catch (Exception $exception) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }
}
