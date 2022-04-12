<?php
declare(strict_types=1);

chdir(__DIR__);
require_once __DIR__ . '/vendor/autoload.php';


$logger = new \TelegramNotifier\Service\Logger();
$logger->setLogLevelThreshold(LogLevel::DEBUG);
$logger->debug('Executing hook_disable');

$pluginConfigManager = Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
$config = $pluginConfigManager->loadConfig();

$optionsManager = Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
$options = $optionsManager->loadOPtions();

$logger->debug('Deleting webhook ');
$url = 'https://api.telegram.org/bot' . $config['telegramBotToken'] . '/deleteWebhook';
$logger->debug($ulr);
$resp = file_get_contents(url);
$logger->debug($resp);