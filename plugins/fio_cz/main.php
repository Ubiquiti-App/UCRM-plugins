<?php

chdir(__DIR__);

require 'vendor/autoload.php';

(function () {
    $builder = new \DI\ContainerBuilder();
    $builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());

    $container = $builder->build();

    $importer = $container->get(\FioCz\Importer::class);

    try {
        $importer->import();
    } catch (Exception $e) {
        $logger = new \FioCz\Service\Logger();
        $logger->error($e->getMessage());
    }
})();
