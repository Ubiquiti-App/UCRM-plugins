<?php

declare(strict_types=1);

namespace MikrotikQueueSync;

use MikrotikQueueSync\Service\Logger;
use MikrotikQueueSync\Service\OptionsManager;
use MikrotikQueueSync\Service\UcrmApi;

class Synchronizer
{
    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    /**
     * @var \RouterosAPI
     */
    private $routerosAPI;

    public function __construct(
        OptionsManager $optionsManager,
        Logger $logger,
        UcrmApi $ucrmApi,
        \RouterosAPI $routerosAPI
    ) {
        $this->optionsManager = $optionsManager;
        $this->logger = $logger;
        $this->ucrmApi = $ucrmApi;
        $this->routerosAPI = $routerosAPI;
    }

    /**
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function sync(): void
    {
        $this->logger->notice('Synchronization started');
        $optionsData = $this->optionsManager->loadOptions();

        if (DEBUG) {
            $this->logger->notice(
                'Mikrotik Connect Data obtained in importer',
                [
                    'IP' => $optionsData->mktip,
                    'Usuario' => $optionsData->mktusr,
                    'Password' => $optionsData->mktpass,
                ]
            );
        }

        if ($this->routerosAPI->connect($optionsData->mktip, $optionsData->mktusr, $optionsData->mktpass)) {
            $this->logger->notice('Successful connection');
				$servicePlans = $this->ucrmApi->query('service-plans'); //Get service Plans for Burst Management
                foreach ($this->ucrmApi->query('clients/services') as $ucrmService) {
                $this->synchronizeService($ucrmService, $servicePlans, $optionsData); //$servicePlans & $optionsData are for Burst Management
            }
        } else {
            $this->logger->notice('Connection failed');
        }

        $this->logger->notice('Synchronization correctly ended / Sincronizado Correctamente');
    }


    private function synchronizeService($ucrmService, $servicePlans, $optionsData): void
    {
        if (DEBUG) {
            $this->logger->notice($ucrmService['id']);
            $this->logger->notice($ucrmService['name']);
	        $this->logger->notice($ucrmService['ipRanges'][0]);
	        $this->logger->notice($ucrmService['servicePlanId']);
        }
		
        if($ucrmService['ipRanges'][0] != '')
		{
		
		//Obtener y formatear Velocidad de UCRM - Obtain and format UCRM  Rate
        $downloadQueue = $this->formatSpeedForMikrotik($ucrmService['downloadSpeed']);
        $uploadQueue = $this->formatSpeedForMikrotik($ucrmService['uploadSpeed']);

        //Obtengo y formateo direccion IP del servicio - Obtaining and formatting service IP Address
        $ipAddress = $ucrmService['ipRanges'][0];
        if (strlen($ipAddress) <= 15) {
            $ipAddress .= '/32';
        }
		
		if (DEBUG) {
		$this->logger->notice(sprintf('Customer plan id: %s', $ucrmService['servicePlanId']));
		}
		
		$servicePlankey = array_search($ucrmService['servicePlanId'], array_column($servicePlans, 'id'));//Searching customer Plan ID in servicePlans array
        
		if (DEBUG) {
        $this->logger->notice(sprintf('Speed will be set to %s/%s for IP: %s', $downloadQueue, $uploadQueue, $ipAddress));
		}
		
		
		if ($optionsData->burstThresholdPercentage != 0 && $optionsData->burstTime != 0)
		{
			if (DEBUG) {
		$this->logger->notice(sprintf('Burst will be set to %s/%s Burst-threshold: %s Burst-time: %s', $servicePlans[$servicePlankey]['downloadBurst'], $servicePlans[$servicePlankey]['uploadBurst'], $optionsData->burstThresholdPercentage, $optionsData->burstTime ));			
			}
			
			$downloadBurst = $this->formatSpeedForMikrotik($servicePlans[$servicePlankey]['downloadBurst']);
			if ($servicePlans[$servicePlankey]['downloadBurst'] < $ucrmService['downloadSpeed']) $downloadBurst = $downloadQueue; // If burst not configured or is less than max-limit, set same as max-limit
			$uploadBurst = $this->formatSpeedForMikrotik($servicePlans[$servicePlankey]['uploadBurst']);
			if ($servicePlans[$servicePlankey]['uploadBurst'] < $ucrmService['uploadSpeed']) $uploadBurst = $uploadQueue; // If burst not configured or is less than max-limit, set same as max-limit
			if ($optionsData->burstThresholdPercentage < 1 || $optionsData->burstThresholdPercentage > 100) {
				$optionsData->burstThresholdPercentage = 50; //If Burst-threshold is not between 1-100, we set 50%
				$this->logger->notice('Burst-Threshold not between 1-100, setting it to 50%');
			} else {$burstThresholdPercentage = $optionsData->burstThresholdPercentage;}
			$burstThresholdPercentage = intval($burstThresholdPercentage);
			if (DEBUG) {
			$this->logger->notice(sprintf('Checked Burst Threshold Percentage: %s', $burstThresholdPercentage));
			}
			$downloadBurstThreshold = $this->formatSpeedForMikrotik($ucrmService['downloadSpeed'] * $burstThresholdPercentage / 100 );
			$uploadBurstThreshold = $this->formatSpeedForMikrotik($ucrmService['uploadSpeed'] * $burstThresholdPercentage / 100);
						
			if ($optionsData->burstTime < 0 || $optionsData->burstTime > 999999999){ 
				$optionsData->burstTime = 32; //if Burst-time not configured or is not between 0 and 999999999 set to 32
				$this->logger->notice('Burst-Time not between 0-999999999, setting it to 32');
			}
			$burstTime = intval($optionsData->burstTime);
			
		} else {
			$downloadBurst = 0;
			$uploadBurst = 0;
			$burstTime = 0;
			$downloadBurstThreshold = 0;
			$uploadBurstThreshold = 0;
		}
		
			if (DEBUG) {
            $this->logger->notice(
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
            $this->logger->notice(
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
		$this->routerosAPI->write('=burst-time=' . $burstTime . '/' . $burstTime, true);
		$mktARRAY2 = $this->routerosAPI->parseResponse( /* necessary to clean buffer after a command */
            $this->routerosAPI->read(false)
        );
		} else {
			if($optionsData->addQueue == 1){
			//Envio comando por API a Mikrotik - Sending API Commands to Mikrotik
			$this->routerosAPI->write('/queue/simple/add', false);
			$this->routerosAPI->write('=target=' . $ipAddress, false);
			$this->routerosAPI->write('=max-limit=' . $uploadQueue . '/' . $downloadQueue, false);
			$this->routerosAPI->write('=burst-limit=' . $uploadBurst . '/' . $downloadBurst, false);
			$this->routerosAPI->write('=burst-threshold=' . $uploadBurstThreshold . '/' . $downloadBurstThreshold, false);
			$this->routerosAPI->write('=burst-time=' . $burstTime . '/' . $burstTime, true);
			$mktARRAY2 = $this->routerosAPI->parseResponse( /* necessary to clean buffer after a command */
            $this->routerosAPI->read(false)
			);	
			}
		}

        if (DEBUG) {
            $this->logger->notice('Commands have been send. / Comandos enviados a Mikrotik');
        }
		} else {$this->logger->notice(sprintf('Service number %s has no IP address associated', $ucrmService['id']));}
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
