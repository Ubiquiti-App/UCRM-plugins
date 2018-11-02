<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/bootstrap.php";

use MVQN\UCRM\Plugins\Log;

/**
 * main.php
 *
 * A script that is executed every time a manual or scheduled execution of this plugin is requested.
 *
 * Use an immediately invoked function here to prevent pollution of the global namespace.
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
(function()
{
    // Notify the user that nothing actually happens here!
    Log::warning("This plugin does not have any manual or scheduled functionality, ignoring!");

})();
