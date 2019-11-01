<?php

declare(strict_types=1);

namespace BackupSyncDropbox\DataProvider;

use BackupSyncDropbox\Data\UnmsBackup;
use BackupSyncDropbox\Utility\NmsSettings;
use DateTimeImmutable;
use DateTimeZone;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

final class BackupDataProvider
{
    /**
     * @var UnmsApi
     */
    private $unmsApi;

    /**
     * @var NmsSettings
     */
    private $nmsSettings;

    public function __construct(UnmsApi $unmsApi, NmsSettings $nmsSettings)
    {
        $this->unmsApi = $unmsApi;
        $this->nmsSettings = $nmsSettings;
    }

    /**
     * @return UnmsBackup[]
     */
    public function getListOfUnmsBackups(): array
    {
        $list = [];
        $nmsTimeZone = $this->nmsSettings->getTimeZone();

        $data = $this->unmsApi->get('nms/backups');
        foreach ($data as $item) {
            if ($item['state'] !== 'success') {
                continue;
            }

            $list[] = $this->createUnmsBackup($item, $nmsTimeZone);
        }

        return $list;
    }

    private function createUnmsBackup(array $item, DateTimeZone $nmsTimeZone): UnmsBackup
    {
        $createdAt = (new DateTimeImmutable($item['createdAt']))->setTimezone($nmsTimeZone);

        return new UnmsBackup(
            $item['id'],
            sprintf(
                'unms-backup-%s_%s.unms',
                $createdAt->format('Ymd-Hi'),
                $item['id']
            )
        );
    }
}
