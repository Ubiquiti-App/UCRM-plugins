<?php

// __DIR__ means "directory of _this_ file, i.e. where main.php is"
define('EXAMPLE_PLUGIN_BASE_DIRECTORY', __DIR__);
// now we are sure that we're currently running in this directory
chdir(EXAMPLE_PLUGIN_BASE_DIRECTORY);

require_once 'src/Logger.php';

(function () {
    $logger = new \SampleLogger\Logger();
    $logger->log('Plugin\'s directory: ' . EXAMPLE_PLUGIN_BASE_DIRECTORY);
    $logger->log('Finished');
})();
