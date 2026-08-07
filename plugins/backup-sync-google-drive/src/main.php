<?php

use BackupSyncGoogleDrive\Handler\BackupHandler;
use BackupSyncGoogleDrive\Service\UnmsApiGoogleDrive;
use BackupSyncGoogleDrive\Utility\LogCleaner;
use BackupSyncGoogleDrive\Utility\Logger;
use BackupSyncGoogleDrive\Utility\NmsSettings;
use BackupSyncGoogleDrive\Utility\Strings;
use BackupSyncGoogleDrive\Utility\ThrowableFormatter;
use DI\ContainerBuilder;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleServiceDrive;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;
use Psr\Log\LoggerInterface;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

require_once __DIR__ . '/vendor/autoload.php';

$pluginLogManager = PluginLogManager::create();
$logger = new Logger($pluginLogManager);

$configManager = PluginConfigManager::create();
$config = $configManager->loadConfig();

$unmsApiToken = Strings::trimNonEmpty($config['unmsApiToken'] ?? null);
if (! is_string($unmsApiToken)) {
    $logger->error('Provided UNMS API token is invalid.');

    exit(1);
}

$googleServiceAccountKeyJson = Strings::trimNonEmpty($config['googleServiceAccountKey'] ?? null);
if (! is_string($googleServiceAccountKeyJson)) {
    $logger->error('Google service account JSON key is not configured.');

    exit(1);
}

$googleServiceAccountKey = json_decode($googleServiceAccountKeyJson, true);
if (! is_array($googleServiceAccountKey)) {
    $logger->error('Google service account JSON key is not valid JSON.');

    exit(1);
}

$googleSharedDriveId = Strings::trimNonEmpty($config['googleSharedDriveId'] ?? null);
if (! is_string($googleSharedDriveId)) {
    $logger->error('Google Shared Drive ID is not configured.');

    exit(1);
}

$googleRootFolderName = Strings::trimNonEmpty($config['googleRootFolderName'] ?? null) ?? 'UISP Backups';

try {
    $googleClient = new GoogleClient();
    $googleClient->setApplicationName('UISP Backup Sync');
    $googleClient->setAuthConfig($googleServiceAccountKey);
    $googleClient->setScopes([GoogleServiceDrive::DRIVE]);

    $googleDriveService = new GoogleServiceDrive($googleClient);

    $adapter = new GoogleDriveAdapter(
        $googleDriveService,
        $googleRootFolderName,
        [
            'teamDriveId' => $googleSharedDriveId,
        ]
    );
    $filesystem = new Filesystem($adapter);

    $builder = new ContainerBuilder();
    $builder->addDefinitions(
        [
            Filesystem::class => $filesystem,
            UnmsApiGoogleDrive::class => UnmsApiGoogleDrive::create($unmsApiToken),
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

    // initiate sync
    $container->get(BackupHandler::class)->sync();
} catch (\Throwable $throwable) {
    $logger->error(ThrowableFormatter::describe($throwable));
}
