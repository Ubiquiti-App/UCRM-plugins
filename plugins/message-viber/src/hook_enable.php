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
$optionsManager = UcrmOptionsManager::create();
$options = $optionsManager->loadOPtions();
$url = 'https://chatapi.viber.com/pa/set_webhook';
$data = array( 
    'url' => $options->pluginPublicUrl,
    'send_name' => True,
    'send_photo'=> True,
    'event_types' => array( 'delivered', 'seen', 'failed', 'subscribed', 'unsubscribed', 'conversation_started' )
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
