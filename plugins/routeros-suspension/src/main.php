<?php

use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use UcrmRouterOs\Service\Suspender;

require_once __DIR__ . '/vendor/autoload.php';

(static function () {
    $config = (new PluginConfigManager())->loadConfig();

    $ipAddresses = array_map('trim', explode(PHP_EOL, $config['mikrotikIpAddress']));
    $userNames = array_map('trim', explode(PHP_EOL, $config['mikrotikUserName']));
    $passwords = array_map('trim', explode(PHP_EOL, $config['mikrotikPassword'] ?? ''));

    $configs = [];
    while ($ipAddress = array_shift($ipAddresses)) {
        $configs[] = array_merge($config, [
            'mikrotikIpAddress' => $ipAddress,
            'mikrotikUserName' => array_shift($userNames),
            'mikrotikPassword' => array_shift($passwords) ?? '',
        ]);
    }

    foreach ($configs as $config) {
        (new Suspender($config))->suspend();
    }
})();
