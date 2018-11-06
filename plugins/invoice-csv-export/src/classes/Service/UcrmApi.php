<?php

declare(strict_types=1);

namespace App\Service;

use App\Data\UcrmUser;
use App\Exception\CurlException;

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
            $data
        );
    }

    /**
     * @throws CurlException
     */
    public function query(string $endpoint, array $parameters = []): array
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

    /**
     * @throws CurlException
     */
    public function getUser(): ?UcrmUser
    {
        $optionsData = $this->optionsManager->loadOptions();

        try {
            $data = $this->curlExecutor->curlQuery(
                sprintf('%scurrent-user', $optionsData->ucrmPublicUrl),
                [
                    'Content-Type: application/json',
                    'Cookie: PHPSESSID=' . preg_replace('~[^a-zA-Z0-9]~', '', $_COOKIE['PHPSESSID'] ?? ''),
                ]
            );
        } catch (CurlException $exception) {
            if ($exception->getCode() === 403) {
                return null;
            }

            throw $exception;
        }

        return new UcrmUser($data);
    }
}
