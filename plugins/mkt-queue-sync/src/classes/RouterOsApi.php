<?php

declare(strict_types=1);

namespace MikrotikQueueSync;

use RouterOS\Client;
use RouterOS\Query;

class RouterOsApi
{
    /**
     * @var Client
     */
    private $client;

    /**
     * Charset used on the router (plugin "Mikrotik charset"). UISP data is always UTF-8:
     * everything sent is converted to this charset and everything read is converted back to UTF-8.
     *
     * @var string
     */
    private $charset;

    public function __construct(Client $client, string $charset = 'UTF-8')
    {
        $this->client = $client;
        $this->charset = $charset;
    }

    public static function create(array $config): self
    {
        $client = new Client(
            [
                'host' => $config['mktip'],
                'user' => $config['mktusr'],
                'pass' => (string) $config['mktpass'],
                'port' => (int) ($config['apiport'] ?: 8728),
            ]
        );

        return new self($client, ($config['outCharset'] ?? null) ?: 'UTF-8');
    }

    /**
     * Converts strings (recursively in arrays) between charsets.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function convert($value, string $to, string $from)
    {
        if ($to === $from) {
            return $value;
        }
        if (is_array($value)) {
            return array_map(function ($item) use ($to, $from) {
                return self::convert($item, $to, $from);
            }, $value);
        }

        return is_string($value) ? mb_convert_encoding($value, $to, $from) : $value;
    }

    public function print(string $endpoint): array
    {
        return $this->read(new Query($endpoint . '/print'));
    }

    public function printwhere(string $endpoint, array $params): array
    {
        $query = new Query($endpoint);
        foreach ($params as $param) {
            $query->add(self::convert($param, $this->charset, 'UTF-8'));
        }

        return $this->read($query);
    }

    /**
     * @return string[] router errors
     */
    public function add(string $endpoint, array $sentences): array
    {
        return $this->writeAll($endpoint . '/add', $sentences);
    }

    /**
     * @return string[] router errors
     */
    public function set(string $endpoint, array $sentences): array
    {
        return $this->writeAll($endpoint . '/set', $sentences);
    }

    private function writeAll(string $command, array $sentences): array
    {
        $errors = [];
        foreach ($sentences as $sentence) {
            $sentence = array_filter($sentence);
            $query = new Query($command);
            foreach ($sentence as $key => $item) {
                $query->add(sprintf('=%s=%s', $key, self::convert((string) $item, $this->charset, 'UTF-8')));
            }
            $error = $this->read($query)['after']['message'] ?? null;
            if ($error) {
                $errors[] = sprintf('Mikrotik says "%s" on %s %s', $error, $command, json_encode($sentence, JSON_UNESCAPED_UNICODE));
            }
        }

        return $errors;
    }

    private function read(Query $query): array
    {
        return self::convert($this->client->query($query)->read(), 'UTF-8', $this->charset);
    }
}
