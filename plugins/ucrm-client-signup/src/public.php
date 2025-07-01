<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

chdir(__DIR__);
define('PROJECT_PATH', __DIR__);

// SameSite=None; Secure; Partitioned configuration is required for session cookies to work properly in iframes
session_start();
header(sprintf('Set-Cookie: PHPSESSID=%s; Secure; Path=/; SameSite=None; Partitioned;', session_id()));

include(PROJECT_PATH . '/includes/initialize.php');
include(PROJECT_PATH . '/includes/api-interpreter.php');
include(PROJECT_PATH . '/includes/ember-html.php');
