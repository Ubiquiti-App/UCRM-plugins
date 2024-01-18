<?php

use DI\ContainerBuilder;
use TicketingTwilio\Plugin;
use TicketingTwilio\Service\Logger;

chdir(__DIR__);

require_once 'vendor/autoload.php';

(static function () {
    try {
        (new ContainerBuilder())->build()->get(Plugin::class)->run();
    } catch (Exception $exception) {
        $logger = new Logger();
        $logger->error($exception->getMessage());
    }
})();
