<?php
declare(strict_types=1);
require_once __DIR__."/bootstrap.php";

use MVQN\REST\RestClient;
use MVQN\UCRM\Plugins\Config;

//$countries = RestClient::get("/countries");
//print_r($countries);

/*
echo Config::getSmtpTransport()."\n";
echo Config::getSmtpHost()."\n";
echo Config::getSmtpPort()."\n";
echo Config::getSmtpEncryption()."\n";
echo Config::getSmtpAuthentication()."\n";
echo Config::getSmtpUsername()."\n";
echo Config::getSmtpPassword()."\n";
*/

$variables = "rspaeth@mvqn.net, %ASSIGNED_USER%, infor@mvqn.net, %ASSIGNED_GROUP%";
$replacements = [ "ASSIGNED_USER" => "TEST1", "ASSIGNED_GROUP" => "TEST2" ];

/*
echo \MVQN\UCRM\Plugins\Controllers\EventController::replaceVariables($variables, $replacements)."\n";
echo \MVQN\UCRM\Plugins\Controllers\EventController::replaceVariablesFunc($variables,
    function($variable)
    {
        switch($variable)
        {
            case "ASSIGNED_USER": return "USER";
            case "ASSIGNED_GROUP": return "GROUP";
            default: return $variable;
        }

    }
)."\n";
*/