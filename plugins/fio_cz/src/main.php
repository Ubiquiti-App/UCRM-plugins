<?php

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

(function ($debug) {
    $logger = new \FioCz\Service\Logger($debug);
    $logger->info('CLI process started');
    $builder = new \DI\ContainerBuilder();
    $builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());

    $container = $builder->build();

    $importer = $container->get(\FioCz\Importer::class);

    try {
        $importer->import();
    } catch (Exception $e) {
        $logger = new \FioCz\Service\Logger($debug);
        echo $e->getMessage();
        $logger->error($e->getMessage());
    }
    echo "\n";
    $logger->info('CLI process ended');
})(($argv[1] ?? '') === '--verbose');
