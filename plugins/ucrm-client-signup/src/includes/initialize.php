<?php

# SDK Data Variables
$configManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
$config = $configManager->loadConfig();

$optionsManager = \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
$options = $optionsManager->loadOptions();

## Just a unique key to give to ember for extra security when making requests
$key = base64_encode(random_bytes(48));

$dataUrl = PROJECT_PATH . '/data/';
\Ucsp\Interpreter::setDataUrl($dataUrl);
\Ucsp\Interpreter::setFrontendKey($key);
