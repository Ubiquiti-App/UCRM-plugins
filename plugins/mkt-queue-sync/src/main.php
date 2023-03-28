<?php

use MikrotikQueueSync\Synchronizer;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

require_once __DIR__ . '/vendor/autoload.php';

(static function () {

    // Ensure that user is logged in and has permission to view invoices.
    (new Synchronizer())->sync();
})();
http_response_code(200); //Response OK when it's called by a webhook.
