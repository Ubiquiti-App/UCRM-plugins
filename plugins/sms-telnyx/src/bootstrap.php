<?php

declare(strict_types=1);

if (! defined('PLUGIN_ROOT')) {
    define('PLUGIN_ROOT', __DIR__);
}
require_once __DIR__ . '/src/Core.php';
require_once __DIR__ . '/src/Messaging.php';
require_once __DIR__ . '/src/Handlers.php';
require_once __DIR__ . '/src/Notices.php';
require_once __DIR__ . '/src/Router.php';

// Quiet hours, log timestamps and date tokens use the configured zone, not the container's (usually UTC).
$tz = \SmsTelnyx\Config::str('timezone', 'America/Chicago');
date_default_timezone_set(in_array($tz, timezone_identifiers_list(), true) ? $tz : 'America/Chicago');
