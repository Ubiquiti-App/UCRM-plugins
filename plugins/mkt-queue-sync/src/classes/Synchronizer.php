<?php

declare(strict_types=1);

namespace MikrotikQueueSync;

use Ubnt\UcrmPluginSdk\Security\PermissionNames;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

require __DIR__ . '/../vendor/autoload.php';

$GLOBALS['sumDownloadLimitAt'] = 0;
$GLOBALS['sumUploadLimitAt'] = 0;
$GLOBALS['sumDownloadVendido'] = 0;
$GLOBALS['sumUploadVendido'] = 0;

class Synchronizer
{
    public function __construct() {
    }

    public function sync(): void
    {
		
		// Retrieve API connection.
		$ucrmApi = UcrmApi::create();
		$this->ucrmApi = $ucrmApi;
		
		// Retrieve UCRM Config.
		$pluginConfigManager = PluginConfigManager::create();
		$optionsData = $pluginConfigManager->loadConfig();
		
		// Retrieve UCRM Logger.
		$logger = PluginLogManager::create();
		$this->logger = $logger;
		
		// Retrieve RouterOSAPI connection.
		$routerosAPI = new \RouterosAPI();
		$this->routerosAPI = $routerosAPI;
		
		
        $this->logger->appendLog('Synchronization started');
        
        if (DEBUG) {
            $this->logger->appendLog(
                'Mikrotik Connect Data obtained in importer',
                [
					'IP' => $optionsData['mktip'],
                    'Usuario' => $optionsData['mktusr'],
                    'Password' => $optionsData['mktpass'],
                ]
            );
        }

        if ($this->routerosAPI->connect($optionsData['mktip'], $optionsData['mktusr'], $optionsData['mktpass'])) {
            $this->logger->appendLog('Successful connection');
				$servicePlans = $this->ucrmApi->get('service-plans'); //Get service Plans for Burst Management
                foreach ($this->ucrmApi->get('clients/services') as $ucrmService) {
                $this->synchronizeService($ucrmService, $servicePlans, $optionsData); //$servicePlans & $optionsData are for Burst Management
				//break; //Just for testing
            }
        } else {
            $this->logger->appendLog('Connection failed');
        }

        $this->logger->appendLog('Synchronization correctly ended / Sincronizado Correctamente');
	$this->logger->appendLog(sprintf('Sumatoria de Download LimitAt: %s', $GLOBALS['sumDownloadLimitAt']));
	$this->logger->appendLog(sprintf('Sumatoria de Upload LimitAt: %s', $GLOBALS['sumUploadLimitAt']));
	$this->logger->appendLog(sprintf('Sumatoria de DownloadVendido: %s', $GLOBALS['sumDownloadVendido']));
	$this->logger->appendLog(sprintf('Sumatoria de UploadVendido: %s', $GLOBALS['sumUploadVendido']));


    }


    private function synchronizeService($ucrmService, $servicePlans, $optionsData): void
    {
        if (DEBUG) {
            $this->logger->appendLog('ID: ' . $ucrmService['id']);
            $this->logger->appendLog('Nombre: ' . $ucrmService['name']);
	        $this->logger->appendLog('IP: ' . $ucrmService['ipRanges'][0]);
	        $this->logger->appendLog('Plan: ' . $ucrmService['servicePlanId']);
        }
		
        if($ucrmService['ipRanges'][0] != '')
		{
		
		//Obtener y formatear Velocidad de UCRM - Obtain and format UCRM  Rate
        $downloadQueue = $this->formatSpeedForMikrotik(($ucrmService['downloadSpeed']));
        $uploadQueue = $this->formatSpeedForMikrotik(($ucrmService['uploadSpeed']));
		$downloadLimitAtQueue = $this->formatSpeedForMikrotik(($ucrmService['downloadSpeed']*20/100));
		$uploadLimitAtQueue = $this->formatSpeedForMikrotik(($ucrmService['uploadSpeed']*20/100));
		$GLOBALS['sumDownloadLimitAt'] = $GLOBALS['sumDownloadLimitAt'] + ($ucrmService['downloadSpeed']*20/100);
		$GLOBALS['sumUploadLimitAt'] = $GLOBALS['sumUploadLimitAt'] + ($ucrmService['downloadSpeed']*20/100);
		$GLOBALS['sumDownloadVendido'] = $GLOBALS['sumDownloadVendido'] + ($ucrmService['downloadSpeed']);
		$GLOBALS['sumUploadVendido'] = $GLOBALS['sumUploadVendido'] + ($ucrmService['downloadSpeed']);


        //Obtengo y formateo direccion IP del servicio - Obtaining and formatting service IP Address
        $ipAddress = $ucrmService['ipRanges'][0];
        if (strlen($ipAddress) <= 15) {
            $ipAddress .= '/32';
        }
		
		if (DEBUG) {
		$this->logger->appendLog(sprintf('Customer plan id: %s', $ucrmService['servicePlanId']));
		}
		
		$servicePlankey = array_search($ucrmService['servicePlanId'], array_column($servicePlans, 'id'));//Searching customer Plan ID in servicePlans array
        
		if (DEBUG) {
        $this->logger->appendLog(sprintf('Speed will be set to %s/%s for IP: %s', $downloadQueue, $uploadQueue, $ipAddress));
		}
		
		$servicePlanName = $servicePlans[$servicePlankey]['name'];
		
		if ($optionsData['burstThresholdPercentage'] != 0 && $optionsData['burstTime'] != 0)
		{
			if (DEBUG) {
		$this->logger->appendLog(sprintf('Burst will be set to %s/%s Burst-threshold: %s Burst-time: %s', $servicePlans[$servicePlankey]['downloadBurst'], $servicePlans[$servicePlankey]['uploadBurst'], $optionsData['burstThresholdPercentage'], $optionsData['burstTime'] ));			
			}
			
			$priority = $servicePlans[$servicePlankey]['dataUsageLimit']; //Using Service DataUsageLimit as Priority
			if ($priority < 1 || $priority > 8) $priority = '8';
			$downloadBurst = $this->formatSpeedForMikrotik(($servicePlans[$servicePlankey]['downloadBurst']));
			if ($servicePlans[$servicePlankey]['downloadBurst'] < $ucrmService['downloadSpeed']) $downloadBurst = $downloadQueue; // If burst not configured or is less than max-limit, set same as max-limit
			$uploadBurst = $this->formatSpeedForMikrotik(($servicePlans[$servicePlankey]['uploadBurst']));
			if ($servicePlans[$servicePlankey]['uploadBurst'] < $ucrmService['uploadSpeed']) $uploadBurst = $uploadQueue; // If burst not configured or is less than max-limit, set same as max-limit
			if ($optionsData['burstThresholdPercentage'] < 1 || $optionsData['burstThresholdPercentage'] > 100) {
				$optionsData['burstThresholdPercentage'] = 50; //If Burst-threshold is not between 1-100, we set 50%
				$this->logger->appendLog('Burst-Threshold not between 1-100, setting it to 50%');
			} else {$burstThresholdPercentage = $optionsData['burstThresholdPercentage'];}
			$burstThresholdPercentage = intval($burstThresholdPercentage);
			if (DEBUG) {
			$this->logger->appendLog(sprintf('Checked Burst Threshold Percentage: %s', $burstThresholdPercentage));
			}
			$downloadBurstThreshold = $this->formatSpeedForMikrotik($ucrmService['downloadSpeed'] * $burstThresholdPercentage / 100 );
			$uploadBurstThreshold = $this->formatSpeedForMikrotik($ucrmService['uploadSpeed'] * $burstThresholdPercentage / 100);
						
			if ($optionsData['burstTime'] < 0 || $optionsData['burstTime'] > 999999999){ 
				$optionsData['burstTime'] = 32; //if Burst-time not configured or is not between 0 and 999999999 set to 32
				$this->logger->appendLog('Burst-Time not between 0-999999999, setting it to 32');
			}
			$burstTime = intval($optionsData['burstTime']);
			
		} else {
			$downloadBurst = 0;
			$uploadBurst = 0;
			$burstTime = 0;
			$downloadBurstThreshold = 0;
			$uploadBurstThreshold = 0;
			$priority = 8;
		}
		
			if (DEBUG) {
            $this->logger->appendLog(
                'Burst-data:',
                [
                    'Download Burst' => $downloadBurst,
					'Download Burst-Threshold' => $downloadBurstThreshold,
					'Upload Burst' => $uploadBurst,
					'Upload Burst-Threshold' => $uploadBurstThreshold,
                    'Burst-Time' => $burstTime,
                ]
            );
        }		
		
		
        //Obtengo ID de Queue comparando IP - Searching Mikrotik Queue ID searching by IP Address
        $this->routerosAPI->write('/queue/simple/print', false);
        $this->routerosAPI->write('?target=' . $ipAddress, true);
		$mktARRAY = $this->routerosAPI->parseResponse(
            $this->routerosAPI->read(false)
        );
        $id = $mktARRAY[0]['.id'];

        if (DEBUG) {
            $this->logger->appendLog(
                ' ',
                [
                    'Mikrotik Queue ID' => $id,
                ]
            );
        }

        if($id != NULL)
		{
		//Envio comando por API a Mikrotik - Sending API Commands to Mikrotik
        $this->routerosAPI->write('/queue/simple/set', false);
        $this->routerosAPI->write('=.id=' . $id, false);
        $this->routerosAPI->write('=max-limit=' . $uploadQueue . '/' . $downloadQueue, false);
		$this->routerosAPI->write('=burst-limit=' . $uploadBurst . '/' . $downloadBurst, false);
		$this->routerosAPI->write('=burst-threshold=' . $uploadBurstThreshold . '/' . $downloadBurstThreshold, false);
		$this->routerosAPI->write('=limit-at=' . $uploadLimitAtQueue . '/' . $downloadLimitAtQueue, false);
		$this->routerosAPI->write('=priority=' . $priority . '/' . $priority, false);
		$this->routerosAPI->write('=burst-time=' . $burstTime . '/' . $burstTime, true);
		$mktARRAY2 = $this->routerosAPI->parseResponse( /* necessary to clean buffer after a command */
            $this->routerosAPI->read(false)
        );
		
		} else {
			if($optionsData['addQueue'] == true){

			$clientInfo = $this->ucrmApi->get(sprintf('clients/%s', $ucrmService['clientId']));
			if ($clientInfo['clientType'] == 1){
				$queueName = $clientInfo['firstName'] . ' ' . $clientInfo['lastName'] . ' - Service ID:' . $ucrmService['id'];
			} else if ($clientInfo['clientType'] == 2){
				$queueName = $clientInfo['companyName'] . ' - Service ID: ' . $ucrmService['id'];
			} else {
				$queueName = 'Service ID: ' . $ucrmService['id'];
			}
			
			//Envio comando por API a Mikrotik - Sending API Commands to Mikrotik
			$this->routerosAPI->write('/queue/simple/add', false);
			$this->routerosAPI->write('=target=' . $ipAddress, false);
			$this->routerosAPI->write('=name=' . $queueName, false);
			$this->routerosAPI->write('=max-limit=' . $uploadQueue . '/' . $downloadQueue, false);
			$this->routerosAPI->write('=burst-limit=' . $uploadBurst . '/' . $downloadBurst, false);
			$this->routerosAPI->write('=burst-threshold=' . $uploadBurstThreshold . '/' . $downloadBurstThreshold, false);
			$this->routerosAPI->write('=limit-at=' . $uploadLimitAtQueue . '/' . $downloadLimitAtQueue, false);
			$this->routerosAPI->write('=priority=' . $priority . '/' . $priority, false);
			$this->routerosAPI->write('=burst-time=' . $burstTime . '/' . $burstTime, true);
			$mktARRAY2 = $this->routerosAPI->parseResponse( /* necessary to clean buffer after a command */
            $this->routerosAPI->read(false)
			);	
			
			}
		}

        if (DEBUG) {
            $this->logger->appendLog('Commands have been send. / Comandos enviados a Mikrotik');
        }
		} else {$this->logger->appendLog(sprintf('Service number %s has no IP address associated', $ucrmService['id']));}
    }

    private function formatSpeedForMikrotik(float $speed): string
    {
		$speed = round($speed,3);
		$speed = strval($speed);
		if (strpos($speed, '.') == 1) // If Float value, for example 0.768M or 1.5M
						{
							$speed = floatval($speed);
							if($speed < 1) //Bandwith less than 1MB are formated for example from 0.768M to 768k ... 
							{
							$speed = sprintf('%sk', intval($speed * 1000));
							} else { //Bandwith more than 1MB are formated for example from 1.5M to 1560k ... 
							$speed = sprintf('%sk', intval($speed * 1024));
							}
						} else {$speed = sprintf('%sM', intval($speed));}
	return $speed;
 }
}
