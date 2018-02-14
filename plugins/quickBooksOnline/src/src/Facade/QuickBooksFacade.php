<?php

declare(strict_types=1);


namespace QBExport\Facade;


use QBExport\Factory\DataServiceFactory;
use QBExport\Service\Logger;

class QuickBooksFacade
{
    /**
     * @var DataServiceFactory
     */
    private $dataServiceFactory;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(DataServiceFactory $dataServiceFactory, Logger $logger)
    {
        $this->dataServiceFactory = $dataServiceFactory;
        $this->logger = $logger;
    }

    public function logAuthotizationURL()
    {
        $dataService = $this->dataServiceFactory->create(DataServiceFactory::TYPE_URL_GENERATOR);
        $authoritationCodeURL =  $dataService->getOAuth2LoginHelper()->getAuthorizationCodeURL();

        $this->logger->notice(sprintf('Authorization URL: %s', $authoritationCodeURL));
    }
}
