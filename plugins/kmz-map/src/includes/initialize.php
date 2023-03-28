<?php

require_once(PROJECT_PATH . '/includes/config.class.php');


// Error Reporting
error_reporting(E_ALL); // Reports all errors
ini_set('display_errors', 'Off'); // Do not display errors for the end-users (security issue)
ini_set('error_log', PROJECT_PATH . '/data/plugin.log'); // Set a logging file
file_put_contents(PROJECT_PATH . '/data/plugin.log', '', LOCK_EX);

// ## Setup Environment Constants
$ucrm_string = file_get_contents(PROJECT_PATH . '/ucrm.json');
$ucrm_json = json_decode($ucrm_string);
define('UCRM_PUBLIC_URL', $ucrm_json->ucrmPublicUrl);

$config_path = PROJECT_PATH . '/data/config.json';

\KMZMap\Config::initializeStaticProperties($config_path);
