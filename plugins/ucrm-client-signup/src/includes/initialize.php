<?php
# SDK Data Variables
$configManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
$config = $configManager->loadConfig();

$optionsManager = \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
$options = $optionsManager->loadOptions();

## Just a unique key to give to ember for extra security when making requests
$key = password_hash($options->pluginPublicUrl.PROJECT_PATH, PASSWORD_DEFAULT);
define("FRONTEND_PUBLIC_KEY", $key);
