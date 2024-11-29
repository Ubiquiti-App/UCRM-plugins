<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Facade;

use BackupSyncDropbox\Data\UnmsBackup;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

final class BackupFacade
{
    public function __construct(
        private UnmsApi $unmsApi,
        private Filesystem $filesystem,
        private LoggerInterface $logger
    ) {
    }

    public function upload(UnmsBackup $unmsBackup): void
    {
        if ($this->filesystem->fileExists($unmsBackup->filename)) {
            $this->logger->info(sprintf('Skipping file "%s", already exists.', $unmsBackup->filename));

            return;
        }

        $temp = tmpfile();
        fwrite($temp, $this->unmsApi->get(sprintf('nms/backups/%s', $unmsBackup->id)));

        $this->filesystem->writeStream($unmsBackup->filename, $temp);

        $this->logger->info(sprintf('Uploaded file "%s".', $unmsBackup->filename));
    }

    public function deleteExcept(array $filenames): void
    {
        $existing = $this->filesystem->listContents('');

        foreach ($existing as $item) {
            if (
                $item['type'] !== 'file'
                || in_array(mb_strtolower($item['path'], 'UTF-8'), $filenames, true)
            ) {
                continue;
            }

            $this->filesystem->delete($item['path']);

            $this->logger->info(sprintf('Deleted file "%s".', $item['path']));
        }
    }
}
