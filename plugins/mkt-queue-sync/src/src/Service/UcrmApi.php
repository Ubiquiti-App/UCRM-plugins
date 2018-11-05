<?php

declare(strict_types=1);

namespace MikrotikQueueSync\Service;

use MikrotikQueueSync\Exception\CurlException;

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

    public function __construct(CurlExecutor $curlExecutor, OptionsManager $optionsManager)
    {
        $this->curlExecutor = $curlExecutor;
        $this->optionsManager = $optionsManager;
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    public function command(string $endpoint, string $method, array $data): void
    {
        $optionsData = $this->optionsManager->loadOptions();

        $this->curlExecutor->curlCommand(
            sprintf('%sapi/v1.0/%s', $optionsData->ucrmPublicUrl, $endpoint),
            $method,
            [
                'Content-Type: application/json',
                'X-Auth-App-Key: ' . $optionsData->pluginAppKey,
            ],
            json_encode((object) $data)
        );
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    public function query(string $endpoint, array $parameters = [])
    {
        $optionsData = $this->optionsManager->loadOptions();

        return $this->curlExecutor->curlQuery(
            sprintf('%sapi/v1.0/%s', $optionsData->ucrmPublicUrl, $endpoint),
            [
                'Content-Type: application/json',
                'X-Auth-App-Key: ' . $optionsData->pluginAppKey,
            ],
            $parameters
        );
    }
}
