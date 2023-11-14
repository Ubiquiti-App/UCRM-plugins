<?php

chdir(__DIR__);

require 'vendor/autoload.php';

(static function () {
    $builder = new \DI\ContainerBuilder();
    $container = $builder->build();
    $plugin = $container->get(\SmsNotifier\Plugin::class);

    try {
        $plugin->run();

        // cleanup plugin log
        $container->get(\SmsNotifier\Service\LogCleaner::class)->clean();
    } catch (Exception $e) {
        $logger = new \SmsNotifier\Service\Logger();
        $logger->error($e->getMessage());
        $logger->debug($e->getTraceAsString());
    }
})();
