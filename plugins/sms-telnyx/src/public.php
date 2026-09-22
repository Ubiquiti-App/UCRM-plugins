<?php

declare(strict_types=1);

chdir(__DIR__);
require __DIR__ . '/bootstrap.php';

\SmsTelnyx\Router::run();
