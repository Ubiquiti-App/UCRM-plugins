<?php

declare(strict_types=1);

namespace BackupSyncDropbox\Handler;

use BackupSyncDropbox\DataProvider\BackupDataProvider;
use BackupSyncDropbox\Facade\BackupFacade;
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
                $this->backupFacade->upload($backup);
                $filenames[] = $backup->filename;
            }

            $this->backupFacade->deleteExcept($filenames);
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf(
                '%s [code %s]: %s',
                get_class($throwable),
                $throwable->getCode(),
                $throwable->getMessage()
            ));

            $exception = $throwable;

            if (defined('BACKUP_SYNC_DROPBOX_DEBUG') && BACKUP_SYNC_DROPBOX_DEBUG) {
                while ($exception !== null) {
                    if ($exception instanceof \GuzzleHttp\Exception\RequestException
                        && $exception->hasResponse()) {
                        $response = $exception->getResponse();

                        $this->logger->error(sprintf(
                            'HTTP response: %d %s',
                            $response->getStatusCode(),
                            $response->getReasonPhrase()
                        ));

                        $body = (string) $response->getBody();

                        if ($body !== '') {
                            $this->logger->error(sprintf(
                                'HTTP response body: %s',
                                substr($body, 0, 4000)
                            ));
                        }

                    break;
                    }

                    $exception = $exception->getPrevious();
                }
            }
        } finally {
            $this->logger->info('Finished backup synchronization.');
        }
    }
}
