<?php

namespace App\Argentinacae;

set_exception_handler('exception_handler');

function exception_handler(Exception $e)
{
    echo $e->getFile() . ':' . $e->getLine() . '  ' . $e->getMessage();
}
