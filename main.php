<?php
    
    // Changes directory to the current plugin directory
    
    chdir(__DIR__);
    
    // Include the execution PHP file
    
    require __DIR__ . "/src/exec.php";
    
    // Break down packet manager IPs into individual IP
    
    foreach($pmIPs AS $pmIP) {
        
        mtikUpdate($pmIP,$pmAdminUser,$pmAdminPass);
    }
