<?php

declare(strict_types=1);

use UBarcode\Service\TokenInstaller;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

(new TokenInstaller())->install();

$configManager = PluginConfigManager::create();
$config = $configManager->loadConfig();
$ucrmOptionsManager = UcrmOptionsManager::create();
$ucrmOptions = $ucrmOptionsManager->loadOptions();

$logManager = PluginLogManager::create();
$logManager->appendLog('Security token has been automatically generated. You must use it as a part of the URL.');
$logManager->appendLog('');
$logManager->appendLog('Example usage:');
$logManager->appendLog(
    sprintf(
        '<img src="%s_plugins/barcode-generator/public.php?token=%s&code=YOUR_CONTENTS&type=QRCODE&width=200&height=200&color=black">',
        $ucrmOptions->ucrmPublicUrl,
        $config['token'] ?? ''
    )
);
$logManager->appendLog('');
