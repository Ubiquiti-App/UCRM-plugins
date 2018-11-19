<?php

declare(strict_types=1);

use App\Service\TemplateRenderer;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

chdir(__DIR__);

require __DIR__ . '/vendor/autoload.php';

// Retrieve API connection.
$api = UcrmApi::create();

// Ensure that user is logged in and has permission to view invoices.
$security = UcrmSecurity::create();
$user = $security->getUser();
if (! $user || $user->isClient || ! $user->hasViewPermission(PermissionNames::BILLING_INVOICES)) {
    \App\Http::forbidden();
}

// Retrieve renderer.
$renderer = new TemplateRenderer();

// Process submitted form.
if (
    array_key_exists('organization', $_GET)
    && is_string($_GET['organization'])
    && array_key_exists('since', $_GET)
    && is_string($_GET['since'])
    && array_key_exists('until', $_GET)
    && is_string($_GET['until'])
) {
    $parameters = [
        'organizationId' => $_GET['organization'],
        'createdDateFrom' => $_GET['since'],
        'createdDateTo' => $_GET['until'],
        'status' => [1, 2, 3], // 1 = Unpaid, 2 = Partially paid, 3 = Paid
    ];

    $organization = $api->get('organizations/' . $_GET['organization']);
    $currency = $api->get('currencies/' . $organization['currencyId']);
    $invoices = $api->get('invoices', $parameters);
    $services = $api->get('clients/services', ['organizationId' => $_GET['organization']]);
    $servicePlans = $api->get('service-plans');

    $servicesMap = [];
    foreach ($services as $service) {
        $servicesMap[$service['id']] = $service['servicePlanId'];
    }

    $servicePlansMap = [];
    foreach ($servicePlans as $servicePlan) {
        $servicePlansMap[$servicePlan['id']] = [
            'name' => $servicePlan['name'],
            'totalIssued' => 0,
            'totalPaid' => 0,
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

    $result = [
        'servicePlans' => array_values($servicePlansMap),
        'currency' => $currency['code'],
        'organization' => $parameters['organizationId'],
        'since' => $parameters['createdDateFrom'],
        'until' => $parameters['createdDateTo'],
    ];
}

// Render form.
$organizations = $api->get('organizations');

$optionsManager = UcrmOptionsManager::create();

$renderer->render(
    __DIR__ . '/templates/form.php',
    [
        'organizations' => $organizations,
        'ucrmPublicUrl' => $optionsManager->loadOptions()->ucrmPublicUrl,
        'result' => $result ?? [],
    ]
);
