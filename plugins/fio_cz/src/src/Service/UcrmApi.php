<?php

declare(strict_types=1);

namespace FioCz\Service;

use FioCz\Exception\CurlException;

class UcrmApi
{
    /**
     * @var CurlExecutor
     */
    private $curlExecuter;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    public function __construct(CurlExecutor $curlExecuter, OptionsManager $optionsManager)
    {
        $this->curlExecuter = $curlExecuter;
        $this->optionsManager = $optionsManager;
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    public function command(string $endpoint, string $method, array $data): void
    {
        $optionsData = $this->optionsManager->loadOptions();

        $this->curlExecuter->curlCommand(
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

        return $this->curlExecuter->curlQuery(
            sprintf('%sapi/v1.0/%s', $optionsData->ucrmPublicUrl, $endpoint),
            [
                'Content-Type: application/json',
                'X-Auth-App-Key: ' . $optionsData->pluginAppKey,
            ],
            $parameters
        );
    }
}
