<?php

declare(strict_types=1);


namespace MikrotikQueueSync;

use RouterOS\Client;
use RouterOS\Exceptions\ClientException;
use RouterOS\Exceptions\ConfigException;
use RouterOS\Query;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;

class RouterOsApi
{
    /**
     * @var Client
     */
    private $client;

    /*public function __construct(Client $client)
    {
        $this->client = $client;
    }*/

    public function __construct($clients)
    {
        $countConstruct = 0;
        foreach ($clients as $client) {
            //echo '<pre>'; var_dump($client); echo '</pre>';
            $this->client[$countConstruct] = $client;
            $countConstruct++;
        }

        foreach ($this->client as $test) {
            //	echo '<pre>'; var_dump($test); echo '</pre>';
        }
    }

    public static function create($logger, ?int $devQty): self
    {
        $config = (new PluginConfigManager())->loadConfig();
        isset($config['apiport']) ? [] : $config['apiport'] = (int) 8728;
        foreach (explode(',', $config['mktip']) as $mktIp) {
            try {
                $client[$devQty] = new Client(
                    [
                        'host' => $mktIp,
                        'user' => $config['mktusr'],
                        'pass' => (string) $config['mktpass'],
                        'port' => (int) $config['apiport'],
                    ]
                );
            } catch (Exception | ConfigException | ClientException $e) {
                echo '<br> ERROR EN LA CONEXION A MIKROTIK IP: ' . $mktIp . '<br>';
                echo $e->getMessage();
                //echo "<br> EL PROGRAMA NO CONTINUARA <br>";
                $logger->appendLog('ERROR EN LA CONEXION A MIKROTIK IP: ' . $mktIp);
                $logger->appendLog($e->getMessage());
                //$logger->appendLog("EL PROGRAMA NO CONTINUARA!!!");
            }
        }

        return new self($client);
    }

    public function wr(int $deviceNum, string $endpoint, $attrs = null): array
    {
        is_null($attrs) ? $response = $this->getClient($deviceNum)->wr([$endpoint]) : $response = $this->getClient($deviceNum)->wr([$endpoint, $attrs]);
        return $response;
    }

    public function print(int $deviceNum, string $endpoint): array
    {
        //$deviceNum = 0;
        return $this->getClient($deviceNum)->write(new Query(sprintf('%s/print', $endpoint)))->read();
    }

    public function remove(int $deviceNum, string $endpoint, array $ids): array
    {
        //$deviceNum = 1;
        if (! $ids) {
            return [];
        }

        $query = new Query(sprintf('%s/remove', $endpoint));
        foreach ($ids as $id) {
            $query->add(sprintf('=.id=%s', $id));
            $result = $this->getClient($deviceNum)->write($query)->read();
        }
        //var_dump($query);
        return $result;
    }

    public function add(int $deviceNum, string $endpoint, array $sentences): void
    {
        //$deviceNum = 1;
        foreach ($sentences as $sentence) {
            $sentence = array_filter($sentence);

            $query = new Query(sprintf('%s/add', $endpoint));
            $orders = '';
            foreach ($sentence as $key => $item) {
                $query->add(sprintf('=%s=%s', $key, $item));
            }

            $this->getClient($deviceNum)->write($query)->read();
        }
    }

    public function addAddressList(int $deviceNum, string $endpoint, array $sentences, string $commentPrefix = 'ucrm_mktsync_'): void
    {
        //$deviceNum = 1;
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

            $this->getClient($deviceNum)->write($query)->read();
        }
    }

    public function set(int $deviceNum, string $endpoint, array $sentences): void
    {
        //$deviceNum = 1;
        foreach ($sentences as $sentence) {
            $query = new Query(sprintf('%s/set', $endpoint));
            $sentence = array_filter($sentence);

            foreach ($sentence as $key => $item) {
                $query->add(sprintf('=%s=%s', $key, $item));
            }

            $this->getClient($deviceNum)->write($query)->read();
        }
    }

    public function getClient($deviceNum): Client
    {
        return $this->client[$deviceNum];
    }
}
