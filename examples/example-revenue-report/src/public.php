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
        'status' => [1, 2, 3],
    ];

    $organization = $api->query('organizations/' . $_GET['organization']);
    $currency = $api->query('currencies/' . $organization['currencyId']);
    $invoices = $api->query('invoices', $parameters);
    $services = $api->query('clients/services', ['organizationId' => $_GET['organization']]);
    $servicePlans = $api->query('service-plans');

    $servicesMap = [];
    foreach ($services as $service) {
        $servicesMap[$service['id']] = $service['servicePlanId'];
    }

    $servicePlansMap = [];
    foreach ($servicePlans as $servicePlan) {
        $servicePlansMap[$servicePlan['id']] = [
            'totalIssued' => 0,
            'totalPaid' => 0,
            'data' => $servicePlan,
        ];
    }

    foreach ($invoices as $invoice) {
        foreach ($invoice['items'] as $invoiceItem) {
            if ($invoiceItem['type'] === 'service' && isset($invoiceItem['serviceId']) && isset($servicesMap[$invoiceItem['serviceId']])) {
                $servicePlanId = $servicesMap[$invoiceItem['serviceId']];
                $price = $invoiceItem['total'] + $invoiceItem['discountTotal'];
                $servicePlansMap[$servicePlanId]['totalIssued'] += $price;
                if ($invoice['status'] === 3) {
                    $servicePlansMap[$servicePlanId]['totalPaid'] += $price;
                }
            }
        }
    }

    var_export($servicePlansMap);

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
