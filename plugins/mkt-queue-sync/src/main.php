<?php

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

(function ($debug) {
    define('DEBUG', $debug);
    $builder = new \DI\ContainerBuilder();
    $builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());
    $container = $builder->build();
    try {
        $synchronizer = $container->get(\MikrotikQueueSync\Synchronizer::class);
        $synchronizer->sync();
    } catch (Exception $e) {
        $logger = new \MikrotikQueueSync\Service\Logger($debug);
        echo $e->getMessage();
        $logger->error($e->getMessage());
    }

    echo '\n';
})(($argv[1] ?? '') === '--verbose');
