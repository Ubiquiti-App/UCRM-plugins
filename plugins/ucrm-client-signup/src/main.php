<?php
chdir(__DIR__);

define("PROJECT_PATH", __DIR__);

require_once(PROJECT_PATH.'/src/functions.php');

file_put_contents(PROJECT_PATH.'/data/plugin.log', '', LOCK_EX);

\log_event('System', 'This plugin uses webhooks. Nothing to run.');