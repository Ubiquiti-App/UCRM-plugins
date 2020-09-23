<?php

chdir(__DIR__);

require 'vendor/autoload.php';

(function () {
    $builder = new \DI\ContainerBuilder();
    $builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());
    $container = $builder->build();
    $plugin = $container->get(\SmsNotifier\Plugin::class);
    try {
        $plugin->run();
    } catch (Exception $e) {
        $logger = new \SmsNotifier\Service\Logger();
        $logger->error($e->getMessage());
        $logger->warning($e->getTraceAsString());
    }
})();
