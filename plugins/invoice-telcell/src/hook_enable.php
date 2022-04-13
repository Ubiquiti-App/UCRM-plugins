<?php
declare(strict_types=1);

chdir(__DIR__);
require_once __DIR__ . '/vendor/autoload.php';


$logger = new \Telcell\Service\Logger();
$logger->setLogLevelThreshold(LogLevel::DEBUG);
$logger->debug('Executing hook_enable');

$pluginConfigManager = Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
$config = $pluginConfigManager->loadConfig();

$optionsManager = Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
$options = $optionsManager->loadOPtions();

$api = UcrmApi::create();


$attrKey = 'telcellId';
$attrName = 'Telcell ID';

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