<?php
declare(strict_types=1);
require_once __DIR__."/bootstrap.php";

use MVQN\REST\RestClient;

$countries = RestClient::get("/countries");
print_r($countries);