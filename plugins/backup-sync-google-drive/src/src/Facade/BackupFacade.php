<?php

declare(strict_types=1);

namespace BackupSyncGoogleDrive\Facade;

use BackupSyncGoogleDrive\Data\UnmsBackup;
use BackupSyncGoogleDrive\Service\UnmsApiGoogleDrive;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;

final class BackupFacade
{
    /**
     * @var UnmsApiGoogleDrive
     */
    private $unmsApiGoogleDrive;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UnmsApiGoogleDrive $unmsApiGoogleDrive, Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->unmsApiGoogleDrive = $unmsApiGoogleDrive;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public function alreadyExists(UnmsBackup $unmsBackup): bool
    {
        return $this->filesystem->fileExists($unmsBackup->filename);
    }

    public function upload(UnmsBackup $unmsBackup): void
    {
        if ($this->alreadyExists($unmsBackup)) {
            $this->logger->info(sprintf('Skipping file "%s", already exists.', $unmsBackup->filename));

            return;
        }

        // Backup archives can be several gigabytes. Stream the download to
        // a local temp file and stream that straight into Google Drive
        // (whose adapter uploads via a resumable session in bounded
        // chunks) instead of buffering the whole archive in memory - see
        // UnmsApiGoogleDrive for why.
        $temporaryFile = $this->getTemporaryFile();

        try {
            $this->unmsApiGoogleDrive->getSink(sprintf('nms/backups/%s', $unmsBackup->id), $temporaryFile);
            $resource = fopen($temporaryFile, 'rb+');
            $this->filesystem->writeStream($unmsBackup->filename, $resource);
        } finally {
            unlink($temporaryFile);
        }

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
