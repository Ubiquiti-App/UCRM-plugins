<?php

declare(strict_types=1);

use App\Service\TemplateRenderer;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

chdir(__DIR__);

require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/classes/Argentinacae/formatinvoice.php';

define('DEBUG', false); //Change to true debug comments get shown

// Separate session name so the CRM session cookie forwarded to the SDK is left untouched.
session_name('AFIP_CSRF_SESSION');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => ! empty($_SERVER['HTTPS']),
]);
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve API connection.
$api = UcrmApi::create();

// Ensure that user is logged in and has permission to view invoices.
$security = UcrmSecurity::create();
$user = $security->getUser();
if (
    ! $user
    || $user->isClient
    || $user->isClientScopeUser
    || ! $user->hasEditPermission(PermissionNames::BILLING_INVOICES)
) {
    \App\Http::forbidden();
}
// Retrieve UCRM Config.
$pluginConfigManager = PluginConfigManager::create();
$config = $pluginConfigManager->loadConfig();

// Retrieve Organizations
$organizations = $api->get('organizations');

$count = 0; //Counter to use in foreach

foreach (explode(';', $config['salesPoint']) as $organizacion) {
    $organizacionesSinKey[$count] = explode(',', $organizacion);
    //Search for key where organization name is
    $organizationKey = array_search($organizacionesSinKey[$count][0], array_column($organizations, 'name'));
    if (DEBUG) {
        echo '<pre>';
        print_r($organizationKey);
        echo '</pre>';
    }
    if (DEBUG) {
        var_dump($organizationKey);
    }
    if (
        ! (is_bool($organizationKey) && $organizationKey === false)
        && (
            ! $user->isOrganizationScopeUser
            || (int) $organizations[$organizationKey]['id'] === $user->scopedOrganizationId
        )
    ) {
        $organizaciones[$count] = [
            'name' => $organizacionesSinKey[$count][0],
            'salesPoint' => $organizacionesSinKey[$count][1],
            'activitiesStartDate' => $organizacionesSinKey[$count][2],
            'id' => $organizations[$organizationKey]['id'],
        ];
    }
    $count++;
}
if (DEBUG) {
    echo '<pre>';
    print_r($organizaciones);
    echo '</pre>';
}
if (DEBUG) {
    echo '<pre>';
    print_r($organizations);
    echo '</pre>';
}

// Process submitted form (POST only, with CSRF token).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin') === 'cross-site') {
        \App\Http::forbidden();
    }
    $submittedToken = $_POST['csrf_token'] ?? null;
    if (
        ! is_string($submittedToken)
        || ! hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        \App\Http::forbidden();
    }

    $selected = $_POST['organization'] ?? null;
    // Only values offered by the form (built from the plugin configuration) are accepted.
    $allowedSelections = [];
    $orgNumber = 1;
    foreach ($organizaciones ?? [] as $organizacion) {
        $allowedSelections[] = $organizacion['id'] . ',' . $organizacion['salesPoint'] . ',' . $organizacion['activitiesStartDate'] . ',' . $orgNumber;
        $orgNumber++;
    }
    if (! is_string($selected) || ! in_array($selected, $allowedSelections, true)) {
        \App\Http::forbidden();
    }

    $parameters = explode(',', $selected);
    if ($user->isOrganizationScopeUser && (int) ($parameters[0] ?? 0) !== $user->scopedOrganizationId) {
        \App\Http::forbidden();
    }
    echo '<br> Organizacion seleccionada: ' . htmlspecialchars($parameters[0] ?? '', ENT_QUOTES) . ' Punto de Venta: ' . htmlspecialchars($parameters[1] ?? '', ENT_QUOTES) . ' Fecha de inicio actividades: ' . htmlspecialchars($parameters[2] ?? '', ENT_QUOTES) . '<br>';
    formatInvoice($parameters[0] ?? null, $parameters[1] ?? null, $parameters[2] ?? null, $parameters[3] ?? null);
}

// Render form.
$optionsManager = UcrmOptionsManager::create();

$renderer = new TemplateRenderer();
$renderer->render(
    __DIR__ . '/templates/form.php',
    [
        'organizaciones' => $organizaciones ?? [],
        'csrfToken' => $_SESSION['csrf_token'],
        'ucrmPublicUrl' => $optionsManager->loadOptions()->ucrmPublicUrl,
    ]
);
