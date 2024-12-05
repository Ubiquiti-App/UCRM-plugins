<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Facade;

use BackupSyncDropbox\Data\UnmsBackup;
use BackupSyncDropbox\Service\UnmsApiDropbox;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;

final class BackupFacade
{
    public function __construct(
        private UnmsApiDropbox $unmsApiDropbox,
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

        $temporaryFile = $this->getTemporaryFile();
        $this->unmsApiDropbox->getSink(sprintf('nms/backups/%s', $unmsBackup->id), $temporaryFile);
        $resource = fopen($temporaryFile, 'rb+');
        $this->filesystem->writeStream($unmsBackup->filename, $resource);
        unlink($temporaryFile);

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

    private function getTemporaryFile(): string
    {
        $tempDir = realpath(sys_get_temp_dir());
        assert(is_string($tempDir));
        $tmpFile = tempnam($tempDir, 'ucrmTmpFile');
        assert(is_string($tmpFile));

        return $tmpFile;
    }
}
