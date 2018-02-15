<?php

chdir(__DIR__);

require 'vendor/autoload.php';

(function () {
    $builder = new \DI\ContainerBuilder();
    $builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());
    $container = $builder->build();
    $plugin = $container->get(\QBExport\Plugin::class);
    try {
        $plugin->run();
    } catch (Exception $e) {
        $logger = new \QBExport\Service\Logger();
        $logger->error($e->getMessage());
    }
})();
