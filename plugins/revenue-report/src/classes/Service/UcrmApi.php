<?php

declare(strict_types=1);

namespace App\Service;

use App\Data\PluginData;
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

    /**
     * @var bool
     */
    private $verifyUcrmApiConnection;

    public function __construct(CurlExecutor $curlExecutor, OptionsManager $optionsManager)
    {
        $this->curlExecutor = $curlExecutor;
        $this->optionsManager = $optionsManager;

        $urlData = parse_url(
            $this->getApiUrl($this->optionsManager->loadOptions())
        );
        $this->verifyUcrmApiConnection = ! ($urlData
            && strtolower($urlData['host']) === 'localhost'
            && strtolower($urlData['scheme']) === 'https'
        );
    }

    /**
     * @throws CurlException
     */
    public function command(string $endpoint, string $method, array $data): void
    {
        $optionsData = $this->optionsManager->loadOptions();

        $this->curlExecutor->curlCommand(
            sprintf('%sapi/v1.0/%s', $this->getApiUrl($optionsData), $endpoint),
            $method,
            [
                'Content-Type: application/json',
                'X-Auth-App-Key: ' . $optionsData->pluginAppKey,
            ],
            $data,
            $this->verifyUcrmApiConnection
        );
    }

    /**
     * @throws CurlException
     */
    public function query(string $endpoint, array $parameters = []): array
    {
        $optionsData = $this->optionsManager->loadOptions();

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

    /**
     * @throws CurlException
     */
    public function getUser(): ?UcrmUser
    {
        $optionsData = $this->optionsManager->loadOptions();

        try {
            $data = $this->curlExecutor->curlQuery(
                sprintf('%scurrent-user', $this->getApiUrl($optionsData)),
                [
                    'Content-Type: application/json',
                    'Cookie: PHPSESSID=' . preg_replace('~[^a-zA-Z0-9]~', '', $_COOKIE['PHPSESSID'] ?? ''),
                ],
                [],
                $this->verifyUcrmApiConnection
            );
        } catch (CurlException $exception) {
            if ($exception->getCode() === 403) {
                return null;
            }

            throw $exception;
        }

        return new UcrmUser($data);
    }

    private function getApiUrl(PluginData $optionsData): string
    {
        return ($optionsData->ucrmLocalUrl ?? false) ?: $optionsData->ucrmPublicUrl;
    }
}
