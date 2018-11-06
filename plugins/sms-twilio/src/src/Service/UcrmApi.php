<?php

declare(strict_types=1);


namespace SmsNotifier\Service;


use SmsNotifier\Exception\CurlException;

class UcrmApi
{
    /**
     * @var CurlExecutor
     */
    private $curlExecutor;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var bool
     */
    private $verifyUcrmApiConnection;

    public function __construct(CurlExecutor $curlExecutor, OptionsManager $optionsManager)
    {
        $this->curlExecutor = $curlExecutor;
        $this->optionsManager = $optionsManager;

        $optionsData = $this->optionsManager->load();
        $apiUrl = (property_exists($optionsData, 'ucrmLocalUrl') && $optionsData->ucrmLocalUrl)
            ? $optionsData->ucrmLocalUrl
            : $optionsData->ucrmPublicUrl;
        $this->verifyUcrmApiConnection = strpos($apiUrl, 'https://localhost') !== 0;
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    public function command(string $endpoint, string $method, array $data): void
    {
        $optionsData = $this->optionsManager->load();

        $this->curlExecutor->curlCommand(
            sprintf('%sapi/v1.0/%s', $optionsData->ucrmPublicUrl, $endpoint),
            $method,
            [
                'Content-Type: application/json',
                'X-Auth-App-Key: ' . $optionsData->pluginAppKey,
            ],
            json_encode((object)$data),
            $this->verifyUcrmApiConnection
        );
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    public function query(string $endpoint, array $parameters = []): array
    {
        $optionsData = $this->optionsManager->load();

        return $this->curlExecutor->curlQuery(
            sprintf('%sapi/v1.0/%s', $optionsData->ucrmPublicUrl, $endpoint),
            [
                'Content-Type: application/json',
                'X-Auth-App-Key: ' . $optionsData->pluginAppKey,
            ],
            $parameters,
            $this->verifyUcrmApiConnection
        );
    }
}
