<?php

declare(strict_types=1);


namespace UcrmRouterOs\Service;

use RouterOS\Client;
use RouterOS\Query;

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

    public static function create(array $config): self
    {
        $client = new Client(
            [
                'host' => $config['mikrotikIpAddress'],
                'user' => $config['mikrotikUserName'],
                'pass' => (string) $config['mikrotikPassword'],
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

        $query = (new Query(sprintf('%s/remove', $endpoint)))
            ->add(sprintf('=.id=%s', implode(',', $ids)));

        return $this->getClient()->write($query)->read();
    }

    public function add(string $endpoint, array $sentences, string $commentPrefix = 'ucrm_'): void
    {
        foreach ($sentences as $sentence) {
            $sentence = array_filter($sentence);

            if (
                $commentPrefix
                && $sentence['comment'] ?? false
            ) {
                $sentence['comment'] = sprintf('%s%s', $commentPrefix, $sentence['comment']);
            }

            $query = new Query(sprintf('%s/add', $endpoint));

            foreach ($sentence as $key => $item) {
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
