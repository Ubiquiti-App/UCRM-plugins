<?php

chdir(__DIR__);

require_once 'src/Logger.php';

$logger = new \SampleLogger\Logger();

$logger->log(
    sprintf(
        'Executed from public URL: %s',
        file_get_contents("php://input")
    )
);
