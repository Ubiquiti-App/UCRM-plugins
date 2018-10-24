<?php
    
    chdir(__DIR__);
    
    require __DIR__ . '/routeros_api_class.php';
    require __DIR__ . '/ucrmInfo.php';
    // Execute all of the Mikrotik packet manager functions
    
    function mtikUpdate($mtikIP,$mtikUser,$mtikPass) {
        
        // Reference variables from ucrmInfo.php
        
        global $ucrmURL;
        global $services;
        
        // Check to see if the IP in the function is empty, if so, stop running
        
        if(is_null($mtikIP) || !$mtikIP) {
            
            return false;
        } else {
            
            // Pull information from the UCRM
            
            $ucrmQuery = ucrmConnect($ucrmURL,$services);
            
            // Start a new RouterOS API instance
            
            $API = new RouterosAPI();
            
            $API->debug = false;
            $API->connect($mtikIP,$mtikUser,$mtikPass);
            
            // Mark all returned items from the UCRM as separate items
            
            foreach($ucrmQuery as $result) {
                
                // Convert the download speed to human readable
                
                $cDS = round($result['downloadSpeed'],3);
                
                if(strpos($cDS, '.') == 1) {
                    
                    $cDS = floatval($cDS);
                    if($cDS < 1) {
                        
                        $cDS = $cDS * 1000;
                        $cDS = strval($cDS);
                        $cDS .= "k";
                    } else {
                        
                        $cDS = $cDS * 1024;
                        $cDS = strval($cDS);
                        $cDS .= "k";
                    }
                } else {
                    
                    $cDS .= "M";
                }
                
                // Convert the upload speed to human readable
                
                $cUS = round($result['uploadSpeed'],3);
                
                if(strpos($cUS, '.') == 1) {
                    
                    $cUS = floatval($cUS);
                    if($cUS < 1) {
                        
                        $cUS = $cUS * 1000;
                        $cUS = strval($cUS);
                        $cUS .= "k";
                        
                    } else {
                        
                        $cUS = $cUS * 1024;
                        $cUS = strval($cUS);
                        $cUS .= "k";
                    }
                } else {
                    
                    $cUS .= "M";
                }
                
                // Pull the first IP from the IP Ranges field
                
                $cIP = $result['ipRanges'][0];
                
                // Add /32 to the IP for use in querying the routerboard
                
                $cmIP = $cIP."/32";
                
                // Mark the client ID
                
                $cID = $result['clientId'];
                
                // Combine the upload and download speed
                
                $maxlimit = $cUS."/".$cDS;
                
                // Todays current date in month-day-year format
                
                $curDate = date("m-d-Y");
                
                // Format the upload speed in Mikrotik format
                
                $cUSr = $result['uploadSpeed'] * 1000000;
                
                // Format the download speed in Mikrotik format
                
                $cDSr = $result['downloadSpeed'] * 1000000;
                
                // Combine the ROS upload and ROS download speed
                
                $maxlimitr = $cUSr."/".$cDSr;
                
                // Query for a queue named UCRM with the client ID
                
                $API->write('/queue/simple/getall',false);
                $API->write('?name=UCRM'.$cID,true);
                $READ = $API->read(false);
                $ARRAY = $API->parseResponse($READ);
                if(count($ARRAY)>0) {
                    
                    // Query for a queue named UCRMXXXX and a matching target IP
                    
                    $API->write('/queue/simple/getall',false);
                    $API->write('?name=UCRM'.$cID,false);
                    $API->write('?target='.$cmIP,true);
                    $READ = $API->read(false);
                    $ARRAY = $API->parseResponse($READ);
                    if(count($ARRAY)>0) {
                        
                        // Query for a queue named UCRMXXXX, a matching target IP, and a matching max limit
                        
                        $API->write('/queue/simple/getall',false);
                        $API->write('?name=UCRM'.$cID,false);
                        $API->write('?target='.$cmIP,false);
                        $API->write('?max-limit='.$maxlimitr,true);
                        $READ = $API->read(false);
                        $ARRAY = $API->parseResponse($READ);
                        if(count($ARRAY)>0) {
                            
                            // Continue the foreach loop if true
                            
                            continue;
                        } else {
                            
                            // Update the queue if the max limit is wrong
                            
                            $API->write('/queue/simple/set',false);
                            $API->write('=.id=UCRM'.$cID,false);
                            $API->write('=target='.$cIP,false);
                            $API->write('=max-limit='.$maxlimit,false);
                            $API->write('=comment=Updated '.$curDate,true);
                            $READ = $API->read(false);
                            $ARRAY = $API->parseResponse($READ);
                        }
                    } else {
                        
                        // Update the queue if the IP is wrong
                        
                        $API->write('/queue/simple/set',false);
                        $API->write('=.id=UCRM'.$cID,false);
                        $API->write('=target='.$cIP,false);
                        $API->write('=max-limit='.$maxlimit,false);
                        $API->write('=comment=Updated '.$curDate,true);
                        $READ = $API->read(false);
                        $ARRAY = $API->parseResponse($READ);
                    }
                } else {
                    
                    // Add a new queue for the client
                    
                    $API->write('/queue/simple/add',false);
                    $API->write('=name=UCRM'.$cID,false);
                    $API->write('=target='.$cIP,false);
                    $API->write('=max-limit='.$maxlimit,false);
                    $API->write('=comment=Added '.$curDate,true);
                    $READ = $API->read(false);
                    $ARRAY = $API->parseResponse($READ);
                }
            }
            
            $API->disconnect();
        }
    }
