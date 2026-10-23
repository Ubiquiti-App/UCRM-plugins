<?php

declare(strict_types=1);

use MikrotikQueueSync\Synchronizer;

require_once __DIR__ . '/vendor/autoload.php';

// Automatic / manual execution from UCRM
date_default_timezone_set('America/Argentina/Buenos_Aires');
Synchronizer::run();
