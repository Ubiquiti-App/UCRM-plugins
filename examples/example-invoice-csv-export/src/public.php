<?php

declare(strict_types=1);

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

// Create Dependency Injection container.
$builder = new \DI\ContainerBuilder();
$builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());
$container = $builder->build();

// Retrieve API connection.
$api = $container->get(\App\Service\UcrmApi::class);

// Ensure that user is logged in and has permission to view invoices.
$user = $api->getUser();
if (! $user || $user->isClient || ! $user->canView('billing/invoices')) {
    \App\Http::forbidden();
}

// Process submitted form.
if (array_key_exists('organization', $_GET) && array_key_exists('since', $_GET) && array_key_exists('until', $_GET)) {
    $parameters = [
        'organizationId' => $_GET['organization'],
        'createdDateFrom' => $_GET['since'],
        'createdDateTo' => $_GET['until'],
    ];

    $invoices = $api->query('invoices', $parameters);

    $csv = \League\Csv\Writer::createFromFileObject(new SplTempFileObject());

    foreach ($invoices as $invoice) {
        $line = [
            $invoice['id'],
            $invoice['clientId'],
            $invoice['number'],
        ];

        $csv->insertOne($line);
    }

    $csv->output('ucrm-invoices.csv');

    exit;
}

// Render form.
$organizations = $api->query('organizations');

$renderer = $container->get(\App\Service\TemplateRenderer::class);
$renderer->render(
    __DIR__ . '/templates/form.php',
    [
        'organizations' => $organizations,
    ]
);
