<?php

declare(strict_types=1);


namespace MikrotikQueueSync;

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\Exceptions\ConfigException;
use RouterOS\Exceptions\ClientException;
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

    public static function create($logger): self
    {
        $config = (new PluginConfigManager())->loadConfig();
		isset($config['apiport']) ?  [] : $config['apiport'] = (int) 8728;
		try {
		$client = new Client(
            [
                'host' => $config['mktip'],
                'user' => $config['mktusr'],
                'pass' => (string) $config['mktpass'],
				'port' => (int) $config['apiport'],
			]
        );
		} catch (Exception | ConfigException | ClientException $e) {
        echo "<br> ERROR EN LA CONEXION A MIKROTIK <br>";
		echo $e->getMessage();
		echo "<br> EL PROGRAMA NO CONTINUARA <br>";
		$logger->appendLog("ERROR EN LA CONEXION A MIKROTIK");
        $logger->appendLog($e->getMessage());
		$logger->appendLog("EL PROGRAMA NO CONTINUARA!!!");
		}
        

        return new self($client);
    }

    public function wr(string $endpoint, $attrs = NULL): array
    {
        is_null($attrs) ? $response = $this->getClient()->wr([$endpoint]) : $response = $this->getClient()->wr([$endpoint, $attrs]);
		return $response;
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
        $result = $this->getClient()->write($query)->read();
		}
		//var_dump($query);
        return $result;
    }

    public function add(string $endpoint, array $sentences): void
    {
        foreach ($sentences as $sentence) {
            $sentence = array_filter($sentence);
			
			$query = new Query(sprintf('%s/add', $endpoint));
			$orders = '';
            foreach ($sentence as $key => $item) {
              $query->add(sprintf('=%s=%s', $key, $item));
			
            }
			
			$this->getClient()->write($query)->read();
			
        }
    }
	
	 public function addAddressList(string $endpoint, array $sentences, string $commentPrefix = 'ucrm_mktsync_'): void
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
	
	public function set(string $endpoint, array $sentences): void
    {
		foreach ($sentences as $sentence) {
            $query = new Query(sprintf('%s/set',$endpoint));
			$sentence = array_filter($sentence);

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
