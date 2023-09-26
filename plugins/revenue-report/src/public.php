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
    $trimNonEmpty = static function (string $value): ?string {
        $value = trim($value);

        return $value === '' ? null : $value;
    };

    $parameters = [
        'organizationId' => $trimNonEmpty((string) $_GET['organization']),
        'createdDateFrom' => $trimNonEmpty((string) $_GET['since']),
        'createdDateTo' => $trimNonEmpty((string) $_GET['until']),
        // 1 = Unpaid, 2 = Partially paid, 3 = Paid
        'status' => [1, 2, 3],
    ];
    $parameters = array_filter($parameters);

    // make sure the dates are in YYYY-MM-DD format
    if (($parameters['createdDateFrom'] ?? null) !== null) {
        $parameters['createdDateFrom'] = new \DateTimeImmutable($parameters['createdDateFrom']);
        $parameters['createdDateFrom'] = $parameters['createdDateFrom']->format('Y-m-d');
    }
    if (($parameters['createdDateTo'] ?? null) !== null) {
        $parameters['createdDateTo'] = new \DateTimeImmutable($parameters['createdDateTo']);
        $parameters['createdDateTo'] = $parameters['createdDateTo']->format('Y-m-d');
    }

    $organization = $api->get('organizations/' . $_GET['organization']);
    $currency = $api->get('currencies/' . $organization['currencyId']);
    $invoices = $api->get('invoices', $parameters);

    $services = $api->get('clients/services', [
        'organizationId' => $_GET['organization'],
    ]);

    $servicePlansMap = [];
    foreach ($services as $service) {
        if (! array_key_exists($service['servicePlanId'], $servicePlansMap)) {
            $servicePlansMap[$service['servicePlanId']] = [
                'name' => $service['servicePlanName'],
                'totalIssued' => 0,
                'totalPaid' => 0,
                'servicesIds' => [$service['id']],
            ];
        } else {
            $servicePlansMap[$service['servicePlanId']]['servicesIds'][] = $service['id'];
        }
    }

    foreach ($invoices as $invoice) {
        foreach ($invoice['items'] as $invoiceItem) {
            $price = $invoiceItem['total'] + $invoiceItem['discountTotal'];
            if ($invoiceItem['type'] !== 'service' || $price <= 0) {
                continue;
            }

            foreach ($servicePlansMap as $servicePlanId => $servicePlan) {
                if (! in_array($invoiceItem['serviceId'], $servicePlan['servicesIds'], true)) {
                    continue;
                }

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
        'organization' => $parameters['organizationId'] ?? null,
        'since' => $parameters['createdDateFrom'] ?? null,
        'until' => $parameters['createdDateTo'] ?? null,
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
