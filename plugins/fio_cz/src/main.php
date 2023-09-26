<?php

use FioCz\Service\Logger;

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

(function ($debug) {
    $logger = new \FioCz\Service\Logger($debug);
    $logger->info('CLI process started');
    $startTime = microtime(true);
    $builder = new \DI\ContainerBuilder();

    $container = $builder->build();
    // use the logger with same logging settings everywhere
    $container->set(Logger::class, $logger);

    $importer = $container->get(\FioCz\Importer::class);

    try {
        $importer->import();
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        $logger->error($e->getMessage());
    }
    echo "\n";
    $endTime = microtime(true);
    $logger->info(sprintf('CLI process ended, wall time: %s sec', $endTime - $startTime));
})(($argv[1] ?? '') === '--verbose'); // if invoked with --verbose, increase logging verbosity
