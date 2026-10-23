<?php

declare(strict_types=1);

use MikrotikQueueSync\Synchronizer;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

require_once __DIR__ . '/vendor/autoload.php';

// "Sync" menu: only admins with access to services and plugins
$user = UcrmSecurity::create()->getUser();
if (
    ! $user
    || $user->isClient
    || ! ($user->hasViewPermission(PermissionNames::CLIENTS_SERVICES) || $user->hasEditPermission(PermissionNames::CLIENTS_SERVICES))
    || ! ($user->hasViewPermission(PermissionNames::SYSTEM_PLUGINS) || $user->hasEditPermission(PermissionNames::SYSTEM_PLUGINS))
) {
    \MikrotikQueueSync\Http::forbidden();
}

date_default_timezone_set('America/Argentina/Buenos_Aires');
echo Synchronizer::run()
    ? 'Synchronization finished, check the plugin log / Sincronización finalizada, revise el log del complemento'
    : 'Synchronization failed, check the plugin log / La sincronización falló, revise el log del complemento';
echo '<br>' . htmlspecialchars(Synchronizer::PRO_NOTICE, ENT_QUOTES);
