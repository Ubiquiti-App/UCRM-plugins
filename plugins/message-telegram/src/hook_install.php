<?php

declare(strict_types=1);

use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmSecurity;
use TelegramNotifier\Service\Logger;

chdir(__DIR__);

require_once __DIR__ . '/vendor/autoload.php';

$attrKey = 'telegramId';
$attrName = 'Telegram ID';


$logger = new \TelegramNotifier\Service\Logger();
$logger->setLogLevelThreshold(LogLevel::DEBUG);

$api = UcrmApi::create();
$customattributes = $api->get('custom-attributes');
if (array_search($attrKey, array_column($customattributes, 'key')) === false)
{
    $logger->debug($attrKey . ' custom attribute not found');
    $api->post('custom-attributes', [ 'name' => $attrName, 'attributeType' => 'client', 'clientZoneVisible' => false, ]);
    $logger->debug($attrKey . ' custom attribute created');
} 
else 
{
    $logger->debug($attrKey . ' custom attribute found');
}

