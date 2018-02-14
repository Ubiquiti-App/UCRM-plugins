<?php

declare(strict_types=1);

namespace QBExport\Factory;

use QBExport\Service\OptionsManager;
use QuickBooksOnline\API\DataService\DataService;

class DataServiceFactory
{
    public const TYPE_URL_GENERATOR = 'UrlGenerator';

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    public function __construct(OptionsManager $optionsManager)
    {
        $this->optionsManager = $optionsManager;
    }

    public function create(string $type)
    {
        $pluginData = $this->optionsManager->loadOptions();

        $commonSettings = [
            'auth_mode' => 'oauth2',
            'ClientID' => $pluginData->qbClientId,
            'ClientSecret' => $pluginData->qbClientSecret,
            'baseUrl' => $pluginData->qbBaseUrl,
        ];

        switch ($type) {
            case self::TYPE_URL_GENERATOR:
                $settings = [
                    'scope' => 'com.intuit.quickbooks.accounting',
                    'state' => $this->getStateCSRF(),
                    'RedirectURI' => $pluginData->pluginPublicUrl,
                ];
                break;
            default:
                throw new \InvalidArgumentException('Wrong type');
        }

        return DataService::Configure(array_merge($commonSettings, $settings));
    }

    private function getStateCSRF(): string
    {
        $pluginData = $this->optionsManager->loadOptions();
        if ($pluginData->qbStateCSRF) {
            return $pluginData->qbStateCSRF;
        }

        $pluginData->qbStateCSRF = bin2hex(openssl_random_pseudo_bytes(12));

        $this->optionsManager->updateOptions();

        return $pluginData->qbStateCSRF;
    }
}
