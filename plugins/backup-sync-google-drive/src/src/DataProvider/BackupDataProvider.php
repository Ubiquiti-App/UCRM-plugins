<?php

declare(strict_types=1);

namespace BackupSyncGoogleDrive\DataProvider;

use BackupSyncGoogleDrive\Data\UnmsBackup;
use BackupSyncGoogleDrive\Service\UnmsApiGoogleDrive;
use BackupSyncGoogleDrive\Utility\NmsSettings;
use DateTimeImmutable;
use DateTimeZone;

final class BackupDataProvider
{
    /**
     * @var UnmsApiGoogleDrive
     */
    private $unmsApiGoogleDrive;

    /**
     * @var NmsSettings
     */
    private $nmsSettings;

    public function __construct(UnmsApiGoogleDrive $unmsApiGoogleDrive, NmsSettings $nmsSettings)
    {
        $this->unmsApiGoogleDrive = $unmsApiGoogleDrive;
        $this->nmsSettings = $nmsSettings;
    }

    /**
     * @return UnmsBackup[]
     */
    public function getListOfUnmsBackups(): array
    {
        $list = [];
        $nmsTimeZone = $this->nmsSettings->getTimeZone();

        $data = $this->unmsApiGoogleDrive->get('nms/backups');

        // Newest first: if a run can't get through the whole list (a
        // failure, or simply more pending backups than time/quota allows),
        // the most valuable-for-disaster-recovery backup gets synced
        // before older ones, not after. The API returns them oldest-first.
        usort($data, static function (array $a, array $b): int {
            return strtotime($b['createdAt']) <=> strtotime($a['createdAt']);
        });

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
