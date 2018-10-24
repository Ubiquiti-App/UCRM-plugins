<?php
    
    // Specify the UCRM config file for the plugin
    
    $file = __DIR__ . "/../data/config.json";
    
    // Specify UCRM info file
    
    $uFile = __DIR__ . "/../ucrm.json";
    
    // Get the UCRM config file options
    
    $uConfigInfo = file_get_contents($uFile);
    
    // Decode the UCRM file from JSON to an array
    
    $uConfigs = json_decode($uConfigInfo,true);
    
    $ucrmPublicURL = $uConfigs['ucrmPublicUrl'];
    
    // Get the config file options
    
    $configInfo = file_get_contents($file);
    
    // Decode the file from JSON to an array
    
    $configs = json_decode($configInfo,true);
    
    $pmIP = $configs['pmIP'];
    $pm2IP = $configs['pm2IP'];
    
    $pmAdminUser = $configs['pmAdminUser'];
    
    $pmAdminPass = $configs['pmAdminPass'];
    
    // Specific UCRM URL for private use
    
    $ucrmURL = $ucrmPublicURL."api/v1.0";
    
    // UCRM API key with read or write capability
    
    $ucrmKey = $uConfigs['pluginAppKey'];
    
    // Query used to pull client information
    
    $services = "/clients/services";
    
    // Specifying that I have two Packet Managers in an array (using it with mtikUpdate foreach loop
    
    $pmIPs = array($pmIP,$pm2IP);
    
    // UCRM query function
    
    function ucrmConnect($connURL,$options) {
        
        // Get UCRM Key from variable table
        
        global $ucrmKey;
        
        $sslVerify = parse_url($connURL);
        
        if($sslVerify['scheme'] == 'https') {
            
            $ch = curl_init();
            
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $connURL . $options);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Auth-App-Key: ".$ucrmKey));
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $results = json_decode($response,true);
        } else {
            
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $connURL . $options);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "X-Auth-App-Key: ".$ucrmKey));
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $results = json_decode($response,true);
        }
        return $results;
    }
