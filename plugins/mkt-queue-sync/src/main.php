<?php

chdir(__DIR__);
define("FOO_DEBUG",false);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/routeros_api.class.php';

(function ($debug) {
		// Instancia a RouterOS API - RouterOS API Instance 
		$API = new routeros_api();
		$API->debug = false;
		// Instancia a UCRM - UCRM Instance
		$builder = new \DI\ContainerBuilder();
		$builder->setDefinitionCache(new \Doctrine\Common\Cache\ApcuCache());
		$container = $builder->build();
		$importer = $container->get(\FioCz\Importer::class);
		try {
		list($ucrmDatt, $mktData) = $importer->import(); // Recibo Lista de Servicios y datos de Aceeso a Mikrotik - Receiving Mikrotik access data and Services
			$logger = new \FioCz\Service\Logger($debug); // Logger Instance
			if(FOO_DEBUG)$logger->notice('Datos Mikrotik: ',
					[
                    'IP' => $mktData->mktip,
                    'Usuario' => $mktData->mktusr,
                    'Password' => $mktData->mktpass,
					]
					);
				if(FOO_DEBUG)$logger->error('Despues de datos mkt');
			} catch (Exception $e) {
			$logger = new \FioCz\Service\Logger($debug);
			echo $e->getMessage();
			$logger->error($e->getMessage());
			}
		try {
			if ($API->connect($mktData->mktip, $mktData->mktusr, $mktData->mktpass)) //Mikrotik Api Connect
				{
				foreach ($ucrmDatt as $ucrmClientt) //Acceso a cada servicio - Accessing to every service
					{
					if(FOO_DEBUG)$logger->notice($ucrmClientt['id']);
					if(FOO_DEBUG)$logger->notice($ucrmClientt['name']);
					//Obtener y formatear Velocidad Download de UCRM - Obtain and format UCRM Download Rate
					$downloadQueue=round($ucrmClientt['downloadSpeed'],3);
					if (strpos($downloadQueue, '.') == 1) // If Float value, for example 0.768M or 1.5M
						{
						$downloadQueue = floatval($downloadQueue);
						if($downloadQueue < 1) //Bandwith less than 1MB are formated for example from 0.768M to 768k ... 
							{
							$downloadQueue = $downloadQueue * 1000;
							$downloadQueue = strval($downloadQueue);
							$downloadQueue .="k";
							
							} else { //Bandwith more than 1MB are formated for example from 1.5M to 1560k ... 
							$downloadQueue = $downloadQueue * 1024;
							$downloadQueue = strval($downloadQueue);
							$downloadQueue .="k";
							}
					
						} else {$downloadQueue .="M";}
					if(FOO_DEBUG)$logger->notice($downloadQueue);
					//Obtener y formatear Velocidad Upload de UCRM - Obtain and format UCRM Upload Rate
					$uploadQueue=round($ucrmClientt['uploadSpeed'],3);
					if (strpos($uploadQueue, '.') == 1) // If Float value, for example 0.768M or 1.5M
						{
						$uploadQueue = floatval($uploadQueue);
						if($uploadQueue < 1) //Bandwith less than 1MB are formated for example from 0.768M to 768k ... 
							{
							$uploadQueue = $uploadQueue * 1000;
							$uploadQueue = strval($uploadQueue);
							$uploadQueue .="k";
								
							} else { //Bandwith more than 1MB are formated for example from 1.5M to 1560k ... 
							$uploadQueue = $uploadQueue * 1024;
							$uploadQueue = strval($uploadQueue);
							$uploadQueue .="k";
							}
					
						} else {$uploadQueue .="M";}
					if(FOO_DEBUG)$logger->notice($uploadQueue);
					//Obtengo y formateo direccion IP del servicio - Obtaining and formatting service IP Address
					$ipaddress=$ucrmClientt['ipRanges'][0];
					if (strlen($ipaddress) <= 15)
						{
						$ipaddress .="/32";
						} 
					if(FOO_DEBUG)$logger->notice($ipaddress);
					
					//Obtengo ID de Queue comparando IP - Searching Mikrotik Queue ID searching by IP Address
					//$ipaddress ="192.168.168.101/32"; //Only to force a customer IP - FOR TEST Purposes
					$API->write('/queue/simple/print',false);
					$API->write('?target='.$ipaddress,true);
					$READ = $API->read(false);
					$mktARRAY = $API->parse_response($READ);
					$id = $mktARRAY[0]['.id'];
					if(FOO_DEBUG)$logger->notice($ipaddress);
					if(FOO_DEBUG)$logger->notice(' ',
						[		
						'QUEUE ID' => $id,
						]
						);
					//$uploadQueue ="10M"; //Only to force a Upload Queue Speed - FOR TEST Purposes
					//$downloadQueue ="30M"; //Only to force a Download Queue Speed - FOR TEST Purposes
					if(FOO_DEBUG)$logger->notice($uploadQueue);
					if(FOO_DEBUG)$logger->notice($downloadQueue);
					//Envio comando por API a Mikrotik - Sending API Commands to Mikrotik
					$API->write('/queue/simple/set',false);
					$API->write('=.id='.$id,false);
					$API->write('=max-limit='.$uploadQueue.'/'.$downloadQueue,true);
					$READ = $API->read(false);
					$mktARRAY2 = $API->parse_response($READ);
					if(FOO_DEBUG)$logger->notice('Comandos enviados a Mikrotik');
					}
					$logger->notice('Sincronizado Correctamente');
				}
			} catch (Exception $e) {
			$logger = new \FioCz\Service\Logger($debug);
			echo $e->getMessage();
			$logger->error($e->getMessage());
			}

    echo "\n";

})(($argv[1] ?? '') === '--verbose');
