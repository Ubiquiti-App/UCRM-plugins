<?php

chdir(__DIR__);

require_once 'src/Logger.php';

$logger = new \SampleLogger\Logger();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logger->log(
        sprintf(
            'Public URL HTTP POST body: %s',
            file_get_contents("php://input")
        )
    );
}
