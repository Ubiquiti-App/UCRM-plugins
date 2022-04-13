<?php

chdir(__DIR__);

require 'vendor/autoload.php';

(static function () {
    $plugin = new \Telcell\Plugin();
    try 
    {
        $plugin->run();
    } 
    catch (Exception $e) 
    {
        $logger = new \Telcell\Service\Logger();
        $logger->error($e->getMessage());
        $logger->debug($e->getTraceAsString());
    }
})();
