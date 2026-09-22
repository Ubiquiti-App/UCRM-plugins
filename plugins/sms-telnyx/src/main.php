<?php

declare(strict_types=1);

/*
 * Runs when the plugin is executed manually / on its execution period (keep it at "don't execute automatically").
 * Validates configuration and prunes old job files. Webhooks and the admin page go through public.php.
 */
chdir(__DIR__);
require __DIR__ . '/bootstrap.php';

use SmsTelnyx\Config;
use SmsTelnyx\Log;
use SmsTelnyx\Phone;
use SmsTelnyx\Store;

$problems = [];
if (Config::str('telnyxApiKey') === '') {
    $problems[] = 'Telnyx API key is missing.';
}
if (! Phone::normalize(Config::str('telnyxFromNumber'))) {
    $problems[] = 'Telnyx sending number must be E.164, e.g. +17125551234.';
}
if (Config::str('telnyxPublicKey') === '') {
    $problems[] = 'Telnyx webhook public key is empty — inbound STOP/HELP/replies will be rejected until it is set.';
}
if (Config::str('forwardRepliesTo') !== '' && ! Phone::normalize(Config::str('forwardRepliesTo'))) {
    $problems[] = '"Forward customer replies to" is not a valid phone number.';
}
if (! function_exists('sodium_crypto_sign_verify_detached')) {
    $problems[] = 'PHP sodium extension unavailable — inbound webhooks cannot be verified.';
}
foreach ($problems as $p) {
    Log::warn('Config: ' . $p);
}

// Prune job files older than 180 days.
$dir = PLUGIN_ROOT . '/data/store';
foreach (glob($dir . '/job-*.json') ?: [] as $f) {
    if (filemtime($f) < time() - 180 * 86400) {
        @unlink($f);
    }
}

$hook = Config::str('pluginPublicUrl');
Log::info(sprintf('Plugin check complete: %s. Telnyx webhook URL: %s',
    $problems ? count($problems) . ' problem(s)' : 'configuration OK',
    $hook !== '' ? $hook . (str_contains($hook, '?') ? '&' : '?') . 'hook=telnyx' : '(public URL not available yet)'));
