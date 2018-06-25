<?php
// ## Project paths
define("PROJECT_SRC_PATH", PROJECT_PATH . '/src');
define("CLASSES_PATH", PROJECT_PATH . '/src/classes');

// ## Setup Environment Constants
$ucrm_string = file_get_contents(PROJECT_PATH."/ucrm.json");
$ucrm_json = json_decode($ucrm_string);
define("UCRM_PUBLIC_URL", $ucrm_json->ucrmPublicUrl);
define("UCRM_KEY", $ucrm_json->pluginAppKey);
define("PLUGIN_PUBLIC_URL", $ucrm_json->pluginPublicUrl);
define("UCRM_API_URL", UCRM_PUBLIC_URL.'api/v2.9');

$config_path = PROJECT_PATH."/data/config.json";

// ## Just a unique key to give to ember for extra security when making requests
$key = password_hash(PLUGIN_PUBLIC_URL, PASSWORD_DEFAULT);
define("FRONTEND_PUBLIC_KEY", $key);

// ## Setup user configuration settings, if they exist
if (file_exists($config_path)) {

  $config_string = file_get_contents($config_path);
  $config_json = json_decode($config_string);

  if (!empty($config_json->optionalLogoUrl)) {
    define("LOGO_URL", $config_json->optionalLogoUrl);
  } else {
    define("LOGO_URL", null);
  }
  if (!empty($config_json->optionalFormTitle)) {
    define("FORM_TITLE", $config_json->optionalFormTitle);
  } else {
    define("FORM_TITLE", null);
  }
  if (!empty($config_json->optionalFormDescription)) {
    define("FORM_DESCRIPTION", $config_json->optionalFormDescription);
  } else {
    define("FORM_DESCRIPTION", null);
  }
  if (!empty($config_json->optionalCompletionText)) {
    define("COMPLETION_TEXT", $config_json->optionalCompletionText);
  } else {
    define("COMPLETION_TEXT", 'Thank you for signing up! You will receive an invitation to access your account upon approval.');
  }
  
} else {
  define("FORM_TITLE", null);
  define("LOGO_URL", null);
  define("FORM_DESCRIPTION", null);
  define("COMPLETION_TEXT", 'Thank you for signing up! You will receive an invitation to access your account upon approval.');
}


// ## Project Classes
require_once(CLASSES_PATH.'/ucrm_api.class.php');
require_once(CLASSES_PATH.'/ucrm_handler.class.php');

// ## include project scripts
require_once(PROJECT_SRC_PATH.'/config.php'); // Project configuration
require_once(PROJECT_SRC_PATH.'/functions.php'); // Project functions
