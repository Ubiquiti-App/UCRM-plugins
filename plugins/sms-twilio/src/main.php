<?php

use DI\ContainerBuilder;
use Doctrine\Common\Cache\ApcuCache;

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

(static function () {
    $builder = new ContainerBuilder();
    $builder->setDefinitionCache(new ApcuCache());
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
