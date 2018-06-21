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
            foreach ($this->ucrmApi->query('clients/services') as $ucrmService) {
                $this->synchronizeService($ucrmService);
            }
        } else {
            $this->logger->notice('Connection failed');
        }

        $this->logger->notice('Synchronization correctly ended / Sincronizado Correctamente');
    }


    private function synchronizeService($ucrmService): void
    {
        if (DEBUG) {
            $this->logger->notice($ucrmService['id']);
            $this->logger->notice($ucrmService['name']);
        }

        //Obtener y formatear Velocidad de UCRM - Obtain and format UCRM  Rate
        $downloadQueue = $this->formatSpeedForMikrotik($ucrmService['downloadSpeed']);
        $uploadQueue = $this->formatSpeedForMikrotik($ucrmService['downloadSpeed']);

        //Obtengo y formateo direccion IP del servicio - Obtaining and formatting service IP Address
        $ipAddress = $ucrmService['ipRanges'][0];
        if (strlen($ipAddress) <= 15) {
            $ipAddress .= '/32';
        }

        if (DEBUG) {
            $this->logger->notice(sprintf('Speed will be set to %s/%s for IP: %s', $downloadQueue, $uploadQueue, $ipAddress));
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

        //Envio comando por API a Mikrotik - Sending API Commands to Mikrotik
        $this->routerosAPI->write('/queue/simple/set', false);
        $this->routerosAPI->write('=.id=' . $id, false);
        $this->routerosAPI->write('=max-limit=' . $uploadQueue . '/' . $downloadQueue, true);

        if (DEBUG) {
            $this->logger->notice('Commands have been send. / Comandos enviados a Mikrotik');
        }
    }

    private function formatSpeedForMikrotik(float $speed): string
    {
        return sprintf('%sk', round($speed, 3) * 1024);
    }
}
