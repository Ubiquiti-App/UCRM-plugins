<?php

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use UBarcode\Service\TokenInstaller;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

$logger = new Logger('UBarcode');
$logger->pushHandler(new StreamHandler('data/plugin.log'));

$logManager = PluginLogManager::create();

(new TokenInstaller())->install();

$logger->info('Security token has been generated. Use it as a part of request string.');
