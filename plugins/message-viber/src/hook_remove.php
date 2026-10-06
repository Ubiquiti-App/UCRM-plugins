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


$pluginConfigManager = PluginConfigManager::create();
$config = $pluginConfigManager->loadConfig();

$url = 'https://chatapi.viber.com/pa/set_webhook';
$data = array( 
    'url' => ''
);
$options = array(
    'http' => array(
        'header'  => "X-Viber-Auth-Token: " . $config['viberBotToken'] . "\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { 

}
