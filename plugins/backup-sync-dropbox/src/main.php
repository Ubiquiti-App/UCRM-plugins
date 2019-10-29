<?php

use BackupSyncDropbox\Handler\BackupHandler;
use BackupSyncDropbox\Utility\LogCleaner;
use BackupSyncDropbox\Utility\Logger;
use BackupSyncDropbox\Utility\NmsSettings;
use DI\ContainerBuilder;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Srmklive\Dropbox\Adapter\DropboxAdapter;
use Srmklive\Dropbox\Client\DropboxClient;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UnmsApi;

require_once __DIR__ . '/vendor/autoload.php';

$pluginLogManager = PluginLogManager::create();
$logger = new Logger($pluginLogManager);

$configManager = PluginConfigManager::create();
$config = $configManager->loadConfig();

$dropboxAccessToken = $config['dropboxAccessToken'] ?? null;
if (! is_string($dropboxAccessToken)) {
    $logger->error('Provided Dropbox access token is invalid.');

    exit(1);
}

$unmsApiToken = $config['unmsApiToken'] ?? null;
if (! is_string($unmsApiToken)) {
    $logger->error('Provided UNMS API token is invalid.');

    exit(1);
}

$client = new DropboxClient($config['dropboxAccessToken']);
$adapter = new DropboxAdapter($client);
$filesystem = new Filesystem($adapter, ['case_sensitive' => false]);

$builder = new ContainerBuilder();
$builder->addDefinitions(
    [
        Filesystem::class => $filesystem,
        UnmsApi::class => UnmsApi::create($unmsApiToken),
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
