<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');

require_once __DIR__ . '/vendor/autoload.php';

chdir(__DIR__);
define("PROJECT_PATH", __DIR__);

include(PROJECT_PATH.'/includes/initialize.php');
include(PROJECT_PATH.'/includes/api-interpreter.php');
include(PROJECT_PATH.'/includes/ember-html.php');