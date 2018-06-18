<?php
chdir(__DIR__);

define("PROJECT_PATH", __DIR__);

require_once(PROJECT_PATH.'/src/functions.php');

log_event('System', 'This plugin uses webhooks. Nothing to run.');