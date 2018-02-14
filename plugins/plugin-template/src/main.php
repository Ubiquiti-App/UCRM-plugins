<?php

chdir(__DIR__);

require_once 'src/Logger.php';

(function () {
    $logger = new \SampleLogger\Logger();
    $logger->log('Finished');
})();
