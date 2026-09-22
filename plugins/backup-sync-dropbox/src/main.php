<?php

use BackupSyncDropbox\Handler\BackupHandler;
use BackupSyncDropbox\Service\DropboxClient;
use BackupSyncDropbox\Service\StreamingUnmsApi;
use BackupSyncDropbox\TokenProvider\DropboxTokenProvider;
use BackupSyncDropbox\Utility\LogCleaner;
use BackupSyncDropbox\Utility\Logger;
use BackupSyncDropbox\Utility\NmsSettings;
use BackupSyncDropbox\Utility\Strings;
use DI\ContainerBuilder;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

require_once __DIR__ . '/vendor/autoload.php';

$pluginLogManager = PluginLogManager::create();
$logger = new Logger($pluginLogManager);

$configManager = PluginConfigManager::create();

$config = $configManager->loadConfig();

$unmsApiToken = Strings::trimNonEmpty($config['unmsApiToken'] ?? null);
$debugMode = ! empty($config['debugMode']);

define('BACKUP_SYNC_DROPBOX_DEBUG', $debugMode);

if (! is_string($unmsApiToken)) {
    $logger->error('Provided UNMS API token is invalid.');

    exit(1);
}

try {
    $client = new DropboxClient(
        new DropboxTokenProvider($pluginLogManager, $configManager),
        null,
        64 * 1024 * 1024
    );

    $adapter = new DropboxAdapter($client);
    $filesystem = new Filesystem($adapter, [
        'case_sensitive' => false,
    ]);

    $builder = new ContainerBuilder();
    $builder->addDefinitions(
        [
            Filesystem::class => $filesystem,
            UnmsApi::class => StreamingUnmsApi::create($unmsApiToken),
            UcrmApi::class => UcrmApi::create(),
            PluginLogManager::class => $pluginLogManager,
            LoggerInterface::class => $logger,
        ]
    );
    $container = $builder->build();

    // set default timezone based on UNMS settings
    date_default_timezone_set($container->get(NmsSettings::class)->getTimeZone()->getName());

    // cleanup plugin log
    $container->get(LogCleaner::class)->clean();

    // Remove staging files left behind by interrupted plugin runs.
    // UISP kills plugin processes after 3600 seconds, so anything older
    // than two hours cannot belong to a normally running synchronization.
    foreach (glob(__DIR__ . '/data/*.tmp') ?: [] as $tempFile) {
        if (is_file($tempFile) && filemtime($tempFile) < time() - 7200) {
            unlink($tempFile);

            if (defined('BACKUP_SYNC_DROPBOX_DEBUG') && BACKUP_SYNC_DROPBOX_DEBUG) {
                $logger->info(sprintf(
                    'Removed stale temporary file "%s".',
                    basename($tempFile)
                ));
            }
        }
    }

    // initiate sync
    $container->get(BackupHandler::class)->sync();
} catch (\Throwable $throwable) {
    $logger->error($throwable->getMessage());
}
