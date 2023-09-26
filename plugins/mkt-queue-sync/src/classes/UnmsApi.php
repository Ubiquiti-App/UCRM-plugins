<?php

declare(strict_types=1);


namespace MikrotikQueueSync;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;
use Ubnt\UcrmPluginSdk\Util\Helpers;
use Ubnt\UcrmPluginSdk\Util\Json;

class UnmsApi
{
    private const HEADER_AUTH_TOKEN = 'x-auth-token';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $token;

    public function __construct(Client $client, string $token)
    {
        $this->client = $client;
        $this->token = $token;
    }

    public static function create($logger): self
    {
        $options = (new UcrmOptionsManager())->loadOptions();

        $unmsUrl = ($options->unmsLocalUrl ?: $options->ucrmPublicUrl) ?? '';
        if (! $unmsUrl) {
            throw new ConfigurationException('UCRM URL is missing in plugin configuration.');
        }

        $config = (new PluginConfigManager())->loadConfig();
        if (! ($config['unmsApiToken'] ?? false)) {
            $logger->appendLog('UNMS API token is missing in plugin configuration.');
            throw new ConfigurationException('UNMS API token is missing in plugin configuration.');
        }

        $unmsApiUrl = sprintf('%s/api/v2.1/', rtrim(str_replace('crm/', 'nms/', $unmsUrl), '/'));

        $client = new Client(
            [
                'base_uri' => $unmsApiUrl,
                // If the URL is localhost over HTTPS, do not verify SSL certificate.
                'verify' => $options->unmsLocalUrl !== null
                    ? false
                    : ! Helpers::isUrlSecureLocalhost($unmsApiUrl),
            ]
        );

        return new self($client, $config['unmsApiToken']);
    }

    public function get(string $endpoint, array $query = []): array
    {
        $response = $this->request(
            'GET',
            $endpoint,
            [
                'query' => $query,
            ]
        );

        return Json::decode((string) $response->getBody());
    }

    private function request(string $method, string $endpoint, array $options = []): Response
    {
        return $this->client->request(
            $method,
            // strip slash character from beginning of endpoint to make sure base API URL is included correctly
            ltrim($endpoint, '/'),
            array_merge(
                $options,
                [
                    'headers' => [
                        self::HEADER_AUTH_TOKEN => $this->token,
                    ],
                ]
            )
        );
    }
}
