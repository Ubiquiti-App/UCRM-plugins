<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Handler;

use BackupSyncDropbox\DataProvider\BackupDataProvider;
use BackupSyncDropbox\Facade\BackupFacade;
use Psr\Log\LoggerInterface;

final class BackupHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private BackupFacade $backupFacade,
        private BackupDataProvider $backupDataProvider
    ) {
    }

    public function sync(): void
    {
        $this->logger->info('Starting backup synchronization.');

        try {
            $backups = $this->backupDataProvider->getListOfUnmsBackups();
            $filenames = [];
            foreach ($backups as $backup) {
                $this->backupFacade->upload($backup);
                $filenames[] = $backup->filename;
            }

            $this->backupFacade->deleteExcept($filenames);
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        } finally {
            $this->logger->info('Finished backup synchronization.');
        }
    }
}
