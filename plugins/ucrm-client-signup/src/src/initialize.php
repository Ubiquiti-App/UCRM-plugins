<?php
## Project paths
define("PROJECT_SRC_PATH", PROJECT_PATH . '/src');

## Setup Environment Constants
$ucrm_string = file_get_contents(PROJECT_PATH."/ucrm.json");
$ucrm_json = json_decode($ucrm_string);
define("UCRM_PUBLIC_URL", $ucrm_json->ucrmPublicUrl);
define("UCRM_KEY", $ucrm_json->pluginAppKey);
define("PLUGIN_PUBLIC_URL", $ucrm_json->pluginPublicUrl);
define("UCRM_API_URL", UCRM_PUBLIC_URL.'api/v1.0');
define("PLUGIN_FILES_DIR", PROJECT_PATH.'/data/files/');

$config_path = PROJECT_PATH."/data/config.json";

## Just a unique key to give to ember for extra security when making requests
$key = password_hash(PLUGIN_PUBLIC_URL.PROJECT_PATH, PASSWORD_DEFAULT);
define("FRONTEND_PUBLIC_KEY", $key);


// ## include project scripts
require_once(PROJECT_SRC_PATH.'/config.php'); // Project configuration
require_once(PROJECT_SRC_PATH.'/functions.php'); // Project functions
UCS\Config::initializeStaticProperties($config_path);
