<?php
    
    chdir(__DIR__);
    
    $file = __DIR__ . "uexec.py";
    
    $command = escapeshellcmd($file);
    $output = shell_exec($command);
