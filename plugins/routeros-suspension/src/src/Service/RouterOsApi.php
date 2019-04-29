<?php

declare(strict_types=1);


namespace UcrmRouterOs\Service;

use RouterOS\Client;
use RouterOS\Query;
use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;

class RouterOsApi
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function create(): self
    {
        $config = (new PluginConfigManager())->loadConfig();

        if (
            ! array_key_exists('mikrotikIpAddress', $config)
            || ! array_key_exists('mikrotikUserName', $config)
            || ! array_key_exists('mikrotikPassword', $config)
        ) {
            throw new ConfigurationException('Missing value in plugin configuration.');
        }

        $client = new Client(
            [
                'host' => $config['mikrotikIpAddress'],
                'user' => $config['mikrotikUserName'],
                'pass' => $config['mikrotikPassword'],
            ]
        );

        return new self($client);
    }

    public function print(string $endpoint): array
    {
        return $this->getClient()->write(new Query(sprintf('%s/print', $endpoint)))->read();
    }

    public function remove(string $endpoint, array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $query = new Query(sprintf('%s/remove', $endpoint));
        foreach ($ids as $id) {
            $query->add(sprintf('=.id=%s', $id));
        }

        return $this->getClient()->write($query)->read();
    }

    public function add(string $endpoint, array $values, string $commentPrefix = 'ucrm_'): void
    {
        foreach ($values as $value) {
            array_filter($value);

            if (
                $commentPrefix
                && $value['comment'] ?? false
            ) {
                $value['comment'] = sprintf('%s%s', $commentPrefix, $value['comment']);
            }

            $query = new Query(sprintf('%s/add', $endpoint));

            foreach ($value as $key => $item) {
                $query->add(sprintf('=%s=%s', $key, $item));
            }

            $this->getClient()->write($query)->read();
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
