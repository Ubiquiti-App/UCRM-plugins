<?php
// Error Reporting
error_reporting(E_ALL); // Reports all errors
ini_set('display_errors','Off'); // Do not display errors for the end-users (security issue)
ini_set('error_log', PROJECT_PATH.'/data/plugin.log'); // Set a logging file
file_put_contents(PROJECT_PATH.'/data/plugin.log', '', LOCK_EX);

// ## Setup Environment Constants
$ucrm_string = file_get_contents(PROJECT_PATH."/ucrm.json");
$ucrm_json = json_decode($ucrm_string);
define("UCRM_PUBLIC_URL", $ucrm_json->ucrmPublicUrl);

$config_path = PROJECT_PATH."/data/config.json";

// ## Setup user configuration settings, if they exist
if (file_exists($config_path)) {

  $config_string = file_get_contents($config_path);
  $config_json = json_decode($config_string);

  define("API_KEY", $config_json->requiredGoogleApiKey);
  define("KMZ_FILE", $config_json->requiredKMZFile);

  if (!empty($config_json->optionalLogoUrl)) {
    define("LOGO_URL", $config_json->optionalLogoUrl);
  } else {
    define("LOGO_URL", null);
  }
  if (!empty($config_json->optionalFormDescription)) {
    define("FORM_DESCRIPTION", $config_json->optionalFormDescription);
  } else {
    define("FORM_DESCRIPTION", null);
  }
  if (!empty($config_json->optionalLinkOne)) {
    $link_one_string = $config_json->optionalLinkOne;
    $link_array = explode('|', $link_one_string);
    define("TEXT_ONE", $link_array[0]);
    define("LINK_ONE", $link_array[1]);
  } else {
    define("LINK_ONE", null);
  }
  if (!empty($config_json->optionalLinkTwo)) {
    $link_one_string = $config_json->optionalLinkTwo;
    $link_array = explode('|', $link_one_string);
    define("TEXT_TWO", $link_array[0]);
    define("LINK_TWO", $link_array[1]);
  } else {
    define("LINK_TWO", null);
  }
  
} else {
  define("API_KEY", null);
  define("LOGO_URL", null);
  define("FORM_DESCRIPTION", null);
}