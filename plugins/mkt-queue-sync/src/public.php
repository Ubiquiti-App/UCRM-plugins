<?php

global $data;

if (json_decode(file_get_contents('php://input'), true)) {
    //print_r($json);
    $data['option'] = 'WebHook-sZkmt5aIohKXMmRrnI3DfDk';
} else {
    //print_r($_POST);
    $data = $_GET;
}

use MikrotikQueueSync\Synchronizer;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

require_once __DIR__ . '/vendor/autoload.php';

(static function () {
    global $data;

    // Ensure that user is logged in and has permission to view invoices.
    $security = UcrmSecurity::create();
    $user = $security->getUser();

    if (isset($data['option'])) {
        if ($data['option'] == 'Sync') {
            if (! $user || $user->isClient || ! $user->hasViewPermission(PermissionNames::CLIENTS_SERVICES)) {
                \MikrotikQueueSync\Http::forbidden();
            }
            (new Synchronizer())->sync();
        } elseif ($data['option'] == 'reset-log') {
            if (! $user || $user->isClient || ! $user->hasViewPermission(PermissionNames::CLIENTS_SERVICES)) {
                \MikrotikQueueSync\Http::forbidden();
            }
            $logger = PluginLogManager::create();
            $logger->clearLog();
            echo '<br> Log Cleared';
        } elseif ($data['option'] == 'WebHook-sZkmt5aIohKXMmRrnI3DfDk') {
            (new Synchronizer())->sync();
        }
    } else {
        if (! $user || $user->isClient || ! $user->hasViewPermission(PermissionNames::CLIENTS_SERVICES)) {
            \MikrotikQueueSync\Http::forbidden();
        }
        (new Synchronizer())->sync();
    }
})();
http_response_code(200); //Response OK when it's called by a webhook.
