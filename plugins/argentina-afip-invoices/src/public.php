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

// Retrieve API connection.
$api = UcrmApi::create();

// Ensure that user is logged in and has permission to view invoices.
$security = UcrmSecurity::create();
$user = $security->getUser();
if (! $user || $user->isClient || ! $user->hasViewPermission(PermissionNames::BILLING_INVOICES)) {
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
    if (! (is_bool($organizationKey) && $organizationKey === false)) {
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

// Process submitted form.
  if (array_key_exists('organization', $_GET)) {
      $parameters = $_GET['organization'];
      $parameters = explode(',', $parameters);
      echo '<br> Organizacion seleccionada: ' . $parameters[0] . ' Punto de Venta: ' . $parameters[1] . ' Fecha de inicio actividades: ' . $parameters[2] . '<br>';
      formatInvoice($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
  }

// Render form.
$optionsManager = UcrmOptionsManager::create();

$renderer = new TemplateRenderer();
$renderer->render(
    __DIR__ . '/templates/form.php',
    [
        'organizaciones' => $organizaciones,
        'ucrmPublicUrl' => $optionsManager->loadOptions()->ucrmPublicUrl,
    ]
);
