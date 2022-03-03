<?php

chdir(__DIR__);

require 'vendor/autoload.php';

(static function () {
    $builder = new \DI\ContainerBuilder();
    $builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());
    $container = $builder->build();
    $plugin = $container->get(\ViberNotifier\Plugin::class);
    try {
        $plugin->run();
    } catch (Exception $e) {
        $logger = new \ViberNotifier\Service\Logger();
        $logger->error($e->getMessage());
        $logger->debug($e->getTraceAsString());
    }
})();
