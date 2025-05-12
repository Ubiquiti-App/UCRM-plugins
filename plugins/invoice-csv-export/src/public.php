<?php

declare(strict_types=1);

use App\Service\CsvGenerator;
use App\Service\TemplateRenderer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
$optionsManager = UcrmOptionsManager::create();
$ucrmOptions = $optionsManager->loadOptions();
$request = Request::createFromGlobals();


// Process submitted form.
if ($request->isMethod('POST')) {
    $organizationId = $request->request->get('organization');
    if ($organizationId === null) {
        (new RedirectResponse($ucrmOptions->ucrmPublicUrl))->send();
    }

    $parameters = [
        'organizationId' => $organizationId,
        'createdDateFrom' => null,
        'createdDateTo' => null,
    ];

    try {
        $since = $request->request->get('since');
        if ($since) {
            $parameters['createdDateFrom'] = (new \DateTimeImmutable($since))->format('Y-m-d');
        }
        $until = $request->request->get('until');
        if ($until) {
            $parameters['createdDateTo'] = (new \DateTimeImmutable($until))->format('Y-m-d');
        }
    } catch (Exception $e) {
        (new RedirectResponse($ucrmOptions->ucrmPublicUrl))->send();
    }


    $countries = $api->get('countries');
    $states = array_merge(
        // Canada
        $api->get('countries/states?countryId=54'),
        // USA
        $api->get('countries/states?countryId=249')
    );

    $csvGenerator = new CsvGenerator($countries, $states);

    $invoices = $api->get('invoices', $parameters);

    $csvGenerator->generate('ucrm-invoices.csv', $invoices);

    exit;
}

// Render form.
$renderer = new TemplateRenderer();
$renderer->render(
    __DIR__ . '/templates/form.php',
    [
        'organizations' => $api->get('organizations'),
        'ucrmPublicUrl' => $ucrmOptions->ucrmPublicUrl,
    ]
);
