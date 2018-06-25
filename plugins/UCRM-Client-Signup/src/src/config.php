<?php
// Composer Files
require_once(PROJECT_PATH.'/vendor/autoload.php');

// Error Reporting
error_reporting(E_ALL); // Reports all errors
ini_set('display_errors','Off'); // Do not display errors for the end-users (security issue)
ini_set('error_log', PROJECT_PATH.'/data/plugin.log'); // Set a logging file

// Override the default error handler behavior
set_exception_handler(function($exception) {
 error_log($exception);
});

UcrmApi::setUcrmKey(UCRM_KEY);
UcrmApi::setUcrmApiUrl(UCRM_API_URL);
