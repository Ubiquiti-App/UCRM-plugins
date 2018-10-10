<?php

declare(strict_types=1);

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

$builder = new \DI\ContainerBuilder();
$builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());
$container = $builder->build();

$api = $container->get(\App\Service\UcrmApi::class);
$user = $api->getUser();

if (! $user || $user->isClient || ! $user->canView('billing/invoices')) {
    // User is not logged into UCRM or can't view invoices.
    \App\Http::forbidden();
}
