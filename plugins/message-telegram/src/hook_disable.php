<?php

declare(strict_types=1);

use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use TelegramNotifier\Service\Logger;

chdir(__DIR__);

require_once __DIR__ . '/vendor/autoload.php';

$pluginConfigManager = PluginConfigManager::create();
$config = $pluginConfigManager->loadConfig();

$optionsManager = UcrmOptionsManager::create();
$options = $optionsManager->loadOPtions();

$logger = new \TelegramNotifier\Service\Logger();
$logger->setLogLevelThreshold(LogLevel::DEBUG);

$logger->debug('Deleting webhook ');

$url = 'https://api.telegram.org/bot' . $config['telegramBotToken'] . 'deleteWebhook';
$logger->debug($ulr);

$resp = file_get_contents(url);
$logger->debug($resp);