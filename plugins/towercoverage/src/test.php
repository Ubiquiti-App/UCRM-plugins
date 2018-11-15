<?php
declare(strict_types=1);
require_once __DIR__."/vendor/autoload.php";

use UCRM\Plugins\Data\TowerCoverage;

$xml = file_get_contents(__DIR__."/examples/push-data.xml");

$json = json_encode(new \SimpleXMLElement($xml, LIBXML_NOCDATA));
$data = json_decode($json, true);

$towerCoverage = new TowerCoverage($data);
print_r($towerCoverage);