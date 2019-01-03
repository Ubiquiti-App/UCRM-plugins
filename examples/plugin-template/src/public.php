<?php

chdir(__DIR__);

require_once __DIR__ . '/vendor/autoload.php';

// Get UCRM log manager.
$log = \Ubnt\UcrmPluginSdk\Service\PluginLogManager::create();
$log->appendLog(
    sprintf(
        'Executed from public URL: %s',
        file_get_contents('php://input')
    )
);
