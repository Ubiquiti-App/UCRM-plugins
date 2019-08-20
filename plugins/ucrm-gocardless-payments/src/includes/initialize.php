<?php
  $ucrmSecurity = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();
  $user = $ucrmSecurity->getUser();

  $configManager = \Ubnt\UcrmPluginSdk\Service\PluginConfigManager::create();
  $config = $configManager->loadConfig();

  $ucrmOptionsManager = new \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager();
  $options = $ucrmOptionsManager->loadOptions();

  $public_folder_path = str_replace(".php", "", $options->pluginPublicUrl);
