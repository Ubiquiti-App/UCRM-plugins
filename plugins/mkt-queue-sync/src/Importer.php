<?php

declare(strict_types=1);

namespace FioCz;

use FioCz\Service\Logger;
use FioCz\Service\OptionsManager;
use FioCz\Service\UcrmApi;

class Importer
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
     * @array UcrmDat
     */

    private $ucrmDat = [];


    public function __construct(OptionsManager $optionsManager, Logger $logger, UcrmApi $ucrmApi)
    {
        $this->optionsManager = $optionsManager;
        $this->logger = $logger;
        $this->ucrmApi = $ucrmApi;
    }

    /**
     * @throws Exception\CurlException
     * @throws \Exception
     * @throws \ReflectionException
     */

    public function import(): array
    {

	$optionsData = $this->optionsManager->loadOptions();
	if(FOO_DEBUG)$this->logger->notice(
		'Mikrotik Connect Data obtained in importer',
		[
        'IP' => $optionsData->mktip,
        'Usuario' => $optionsData->mktusr,
        'Password' => $optionsData->mktpass,
        ]
        );
	try {
	$ucrmDat = $this->ucrmApi->query('clients/services');
		} catch (\Exception $e) {
		$this->logger->notice(
		'error',
		[
        'en' => $e,
        ]
		);
        }

	return [$ucrmDat, $optionsData];
	}
}