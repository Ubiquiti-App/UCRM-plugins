<?php

declare(strict_types=1);

use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;

chdir(__DIR__);

require_once __DIR__ . '/vendor/autoload.php';

$attrKey = 'telegramId';
$attrName = 'Telegram ID';


$api = UcrmApi::create();
$customattributes = $api->get('custom-attributes');
if (array_search($attrKey, array_column($customattributes, 'key')) === false)
{
    echo '<br> ' . $attrKey . ' custom attribute not found <br>';
    $api->post('custom-attributes', [ 'name' => $attrName, 'attributeType' => 'client', 'clientZoneVisible' => false, ]);
    echo $attrKey . ' custom attribute created <br>'; 
} 
else 
{
    echo $attrKey . ' custom attribute found <br>';
}

