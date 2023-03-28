<?php

try {
    $payload = @file_get_contents('php://input');

    $interpreter = new \Ucsp\Interpreter();

    $interpreter->run($payload);
    if ($interpreter->isReady()) {
        http_response_code($interpreter->getCode());
        echo $interpreter->getResponse();
        exit();
    }
} catch (\UnexpectedValueException $e) {
    http_response_code($e->getCode());
    echo $e->getMessage();
    exit();
}
