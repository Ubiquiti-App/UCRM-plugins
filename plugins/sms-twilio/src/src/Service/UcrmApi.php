<?php

declare(strict_types=1);


namespace SmsNotifier\Service;


use SmsNotifier\Data\PluginData;
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

        $urlData = parse_url(
            $this->getApiUrl($this->optionsManager->load())
        );
        $this->verifyUcrmApiConnection = ! ($urlData
            && strtolower($urlData['host']) === 'localhost'
            && strtolower($urlData['scheme']) === 'https'
        );
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    public function command(string $endpoint, string $method, array $data): void
    {
        $optionsData = $this->optionsManager->load();

        $this->curlExecutor->curlCommand(
            sprintf('%sapi/v1.0/%s', $this->getApiUrl($optionsData), $endpoint),
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
            sprintf('%sapi/v1.0/%s', $this->getApiUrl($optionsData), $endpoint),
            [
                'Content-Type: application/json',
                'X-Auth-App-Key: ' . $optionsData->pluginAppKey,
            ],
            $parameters,
            $this->verifyUcrmApiConnection
        );
    }

    private function getApiUrl(PluginData $optionsData): string
    {
        return ($optionsData->ucrmLocalUrl ?? false) ?: $optionsData->ucrmPublicUrl;
    }
}
