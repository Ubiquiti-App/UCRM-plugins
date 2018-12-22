<?php
# SDK Data Variables
$configManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
$config = $configManager->loadConfig();

$optionsManager = \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
$options = $optionsManager->loadOptions();

## Just a unique key to give to ember for extra security when making requests
// $key = password_hash($options->pluginPublicUrl.PROJECT_PATH, PASSWORD_DEFAULT); // This does not work
$key = "this_key_should_be_improved";

\Ucsp\Interpreter::setFrontendKey($key);