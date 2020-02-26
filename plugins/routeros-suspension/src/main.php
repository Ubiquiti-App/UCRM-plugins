<?php

use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use UcrmRouterOs\Service\Suspender;

require_once __DIR__ . '/vendor/autoload.php';

(static function () {
    $log = PluginLogManager::create();
    $log->clearLog();

    $config = (new PluginConfigManager())->loadConfig();

    $ipAddresses = array_map('trim', explode(PHP_EOL, $config['mikrotikIpAddress']));
    $userNames = array_map('trim', explode(PHP_EOL, $config['mikrotikUserName']));
    $passwords = array_map('trim', explode(PHP_EOL, $config['mikrotikPassword'] ?? ''));

    if (count($ipAddresses) !== count($userNames)) {
        $log->appendLog('Number of rows in IP Address and User name fields does not match.');
        exit(1);
    }

    $configs = [];
    while ($ipAddress = array_shift($ipAddresses)) {
        $configs[] = array_merge($config, [
            'mikrotikIpAddress' => $ipAddress,
            'mikrotikUserName' => array_shift($userNames),
            'mikrotikPassword' => array_shift($passwords) ?? '',
        ]);
    }

    foreach ($configs as $config) {
        $error = false;
        try {
            $log->appendLog(sprintf('Process is starting [%s, %s]', $config['mikrotikIpAddress'], $config['mikrotikUserName']));
            (new Suspender($config))->suspend();
        } catch (\Exception $exception) {
            $error = true;
            $log->appendLog(sprintf('  - %s', $exception->getMessage()));
        }
        $log->appendLog(sprintf('Process is finished %s%s', $error ? '[ERROR]' : '[OK]', PHP_EOL));
    }
})();