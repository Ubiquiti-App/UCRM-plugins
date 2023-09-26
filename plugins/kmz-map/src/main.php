<?php

chdir(__DIR__);

define('PROJECT_PATH', __DIR__);

file_put_contents(PROJECT_PATH . '/data/plugin.log', 'This plugin uses the public url. Nothing to run.', LOCK_EX);
