<?php

declare(strict_types=1);

use Com\Tecnick\Barcode\Barcode;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use UBarcode\Service\LogCleaner;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

$logger = new Logger('UBarcode');
$logger->pushHandler(new StreamHandler('data/plugin.log', LogLevel::WARNING));

$logManager = PluginLogManager::create();
$logCleaner = new LogCleaner($logManager);
$logCleaner->clean();

$configManager = PluginConfigManager::create();
$request = Request::createFromGlobals();

$config = $configManager->loadConfig();

if ($config['token'] === (string) $request->get('token')) {
    try {
        $barcode = new Barcode();
        $barcode->getBarcodeObj(
            (string) $request->get('type'),
            (string) $request->get('code'),
            (int) $request->get('width'),
            (int) $request->get('height'),
            (string) $request->get('color')
        )->getSvg();
    } catch (Exception $exception) {
        $logger->error($exception->getMessage());
    }
} else {
    $logger->error('Invalid request token', [
        'config' => $config['token'],
        'request' => $request->get('token'),
    ]);
}
