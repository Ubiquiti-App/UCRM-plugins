<?php
declare(strict_types=1);
require_once __DIR__."/bootstrap.php";

use MVQN\REST\RestClient;
use MVQN\UCRM\Plugins\Config;

//$countries = RestClient::get("/countries");
//print_r($countries);
echo Config::getSmtpTransport()."\n";
echo Config::getSmtpHost()."\n";
echo Config::getSmtpPort()."\n";
echo Config::getSmtpEncryption()."\n";
echo Config::getSmtpAuthentication()."\n";
echo Config::getSmtpUsername()."\n";
echo Config::getSmtpPassword()."\n";