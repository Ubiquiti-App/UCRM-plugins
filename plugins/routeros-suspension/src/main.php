<?php

use UcrmRouterOs\Service\Suspender;

require_once __DIR__ . '/vendor/autoload.php';

(static function () {
    (new Suspender())->suspend();
})();
