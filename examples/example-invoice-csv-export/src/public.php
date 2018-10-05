<?php

declare(strict_types=1);

chdir(__DIR__);

require __DIR__ . '/src/functions.php';

$config = loadConfig(__DIR__ . '/ucrm.json');

$user = retrieveCurrentUser($config['ucrmPublicUrl']);

echo 'Api key is: ' . $config['pluginAppKey'];

var_export($user);
