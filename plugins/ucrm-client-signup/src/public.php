<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

chdir(__DIR__);
define('PROJECT_PATH', __DIR__);

// SameSite=None; Secure configuration is required for session cookies to work properly in iframes
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'true');
session_start();

include(PROJECT_PATH . '/includes/initialize.php');
include(PROJECT_PATH . '/includes/api-interpreter.php');
include(PROJECT_PATH . '/includes/ember-html.php');
