<?php
declare(strict_types=1);
require __DIR__.'/vendor/autoload.php';

use MVQN\UCRM\Plugins\Plugin;

/**
 * composer.php
 *
 * A shared script that handles composer script execution from the command line.
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */

// Handle the different command line arguments...
switch ($argv[1]) {
    // Perform initialization of the Plugin libraries and create the auto-generated Settings class.
    case "create":
        Plugin::initialize(__DIR__);
        Plugin::createSettings();
        break;

    // Bundle the 'zip/' directory into a package ready for Plugin installation on the UCRM server.
    case "bundle":
        Plugin::initialize(__DIR__);
        Plugin::bundle(__DIR__, "notifications", __DIR__ . "/.zipignore", __DIR__ . "/../");
        break;

    // TODO: More commands to come!

    default:
        break;
}
