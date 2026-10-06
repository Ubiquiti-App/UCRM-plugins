<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Facade;

use BackupSyncDropbox\Data\UnmsBackup;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

final class BackupFacade
{
    /**
     * @var UnmsApi
     */
    private $unmsApi;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UnmsApi $unmsApi, Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->unmsApi = $unmsApi;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public function upload(UnmsBackup $unmsBackup): void
    {
        if ($this->filesystem->fileExists($unmsBackup->filename)) {
            $this->logger->info(sprintf('Skipping file "%s", already exists.', $unmsBackup->filename));

            return;
        }

        $this->logger->info(sprintf('Downloading "%s" from UISP API.', $unmsBackup->filename));

        $source = $this->unmsApi->getStream(sprintf('nms/backups/%s', $unmsBackup->id));

        $tempPath = __DIR__ . '/../../data/' . $unmsBackup->filename . '.tmp';
        $temp = fopen($tempPath, 'w+b');

        if ($temp === false) {
            if (is_resource($source)) {
                fclose($source);
            }

            throw new \RuntimeException(sprintf('Unable to create temporary file "%s".', $tempPath));
        }

        try {
            $bytesCopied = stream_copy_to_stream($source, $temp);

            if ($bytesCopied === false) {
                throw new \RuntimeException(sprintf(
                    'Unable to download "%s" from UISP API.',
                    $unmsBackup->filename
                ));
            }

            $this->logger->info(sprintf(
                'Downloaded "%s" to temporary file (%d bytes).',
                $unmsBackup->filename,
                $bytesCopied
            ));

            if (is_resource($source)) {
                fclose($source);
                $source = null;
            }

            rewind($temp);

            $this->logger->info(sprintf('Uploading "%s" to Dropbox.', $unmsBackup->filename));

            $this->filesystem->writeStream($unmsBackup->filename, $temp);
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }

            if (is_resource($temp)) {
                fclose($temp);
            }

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
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
}
