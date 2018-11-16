<?php
declare(strict_types=1);
require_once __DIR__."/vendor/autoload.php";

use UCRM\Plugins\Data\Coverage;

$xml = file_get_contents(__DIR__."/examples/push-data.xml");

$json = json_encode(new \SimpleXMLElement($xml, LIBXML_NOCDATA));
$data = json_decode($json, true);

$coverage = new Coverage($data["CustomerDetails"]["CustomerLinkInfo"]["coverage"][0]);

var_dump($coverage);
