<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

chdir(__DIR__);
define('PROJECT_PATH', __DIR__);

include(PROJECT_PATH . '/includes/initialize.php');
include(PROJECT_PATH . '/includes/api-interpreter.php');
include(PROJECT_PATH . '/includes/ember-html.php');
