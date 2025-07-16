<?php

declare(strict_types=1);

use App\HttpGetParametersData;
use App\Service\ServicePlansDataProvider;
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
    $httpParametersData = new HttpGetParametersData();
    $servicePlansDataProvider = new ServicePlansDataProvider($api, $httpParametersData);
    $servicePlans = $servicePlansDataProvider->getServicePlans();

    $organization = $api->get('organizations/' . $_GET['organization']);
    $currency = $api->get('currencies/' . $organization['currencyId']);

    $result = [
        'servicePlans' => array_values($servicePlans),
        'currency' => $currency['code'],
        'organization' => $parameters['organizationId'] ?? null,
        'since' => $httpParametersData->since,
        'until' => $httpParametersData->until,
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
