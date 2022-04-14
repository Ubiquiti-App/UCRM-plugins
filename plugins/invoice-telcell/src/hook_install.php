<?php

declare(strict_types=1);

use Telcell\Service\API;

chdir(__DIR__);

require_once __DIR__ . '/vendor/autoload.php';

$api = new API();
$api->createCustomAttribute('Telcell Wallet ID', 'client', 'string');
$api->createCustomAttribute('Telcell Invoice Number', 'invoice', 'integer');
$api->createCustomAttribute('Telcell Payment Number', 'payment', 'integer');

