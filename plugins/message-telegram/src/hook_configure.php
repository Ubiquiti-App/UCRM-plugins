<?php
declare(strict_types=1);

chdir(__DIR__);
require_once __DIR__ . '/vendor/autoload.php';



$logger = new \TelegramNotifier\Service\Logger();
$logger->setLogLevelThreshold(LogLevel::DEBUG);

$attrKey = 'telegramId';
$attrName = 'Telegram ID';

$api = Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
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

