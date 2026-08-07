<?php

declare(strict_types=1);

namespace BackupSyncGoogleDrive\Handler;

use BackupSyncGoogleDrive\DataProvider\BackupDataProvider;
use BackupSyncGoogleDrive\Facade\BackupFacade;
use BackupSyncGoogleDrive\Utility\ThrowableFormatter;
use Psr\Log\LoggerInterface;

final class BackupHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BackupFacade
     */
    private $backupFacade;

    /**
     * @var BackupDataProvider
     */
    private $backupDataProvider;

    public function __construct(
        LoggerInterface $logger,
        BackupFacade $backupFacade,
        BackupDataProvider $backupDataProvider
    ) {
        $this->logger = $logger;
        $this->backupFacade = $backupFacade;
        $this->backupDataProvider = $backupDataProvider;
    }

    public function sync(): void
    {
        $this->logger->info('Starting backup synchronization.');

        try {
            $backups = $this->backupDataProvider->getListOfUnmsBackups();
            $filenames = [];

            foreach ($backups as $backup) {
                $filenames[] = $backup->filename;

                // A single backup failing to upload (eg. a transient
                // network/API error) should not block the rest of the
                // batch - log and continue. No one-per-run cap here
                // (unlike the sibling backup-sync-dropbox plugin): that
                // exists specifically to survive UISP's 1-hour SIGKILL on
                // Dropbox's slow multi-round-trip chunked upload API;
                // Google Drive's resumable upload is fast enough that
                // syncing everything pending in one run is fine.
                try {
                    $this->backupFacade->upload($backup);
                } catch (\Throwable $throwable) {
                    $this->logger->error(
                        sprintf('Failed to upload "%s": %s', $backup->filename, ThrowableFormatter::describe($throwable))
                    );
                }
            }

            $this->backupFacade->deleteExcept($filenames);
        } catch (\Throwable $throwable) {
            $this->logger->error(ThrowableFormatter::describe($throwable));
        } finally {
            $this->logger->info('Finished backup synchronization.');
        }
    }
}
