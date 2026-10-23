<?php

declare(strict_types=1);

namespace MikrotikQueueSync;

use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;

/**
 * Synchronizes UISP service speeds with Mikrotik simple queues on one router.
 * PRO version (multiple routers, unlimited services, suspension, address-lists, remove ended services...): plugins@franco.ar
 */
class Synchronizer
{
    public const SERVICES_LIMIT = 100;

    public const PRO_NOTICE = 'Mikrotik Queue Sync PRO: multiple routers, unlimited services, suspension, address-lists and more / '
        . 'varios routers, servicios ilimitados, suspensión, address-lists y más: plugins@franco.ar';

    private const QUEUE_ATTRIBUTES = ['max-limit', 'limit-at', 'priority', 'burst-limit', 'burst-threshold', 'burst-time'];

    // Prepared, Active, Suspended, Prepared blocked, Deferred
    private const SYNCED_STATUSES = [0, 1, 3, 4, 6];

    /**
     * @var array
     */
    private $config;

    /**
     * @var PluginLogManager
     */
    private $logger;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    /**
     * @var UnmsApi|null
     */
    private $unmsApi;

    /**
     * @var RouterOsApi
     */
    private $routerOsApi;

    /**
     * @var string
     */
    private $ucrmUri;

    public function __construct(array $config, PluginLogManager $logger)
    {
        $routers = array_values(array_filter(array_map('trim', explode("\n", (string) $config['mktip']))));
        if (count($routers) > 1) {
            $logger->appendLog('Only the first router is synced in the free version / Solo se sincroniza el primer router en la versión gratuita');
            $logger->appendLog(self::PRO_NOTICE);
        }
        $config['mktip'] = $routers[0] ?? '';

        $this->config = $config;
        $this->logger = $logger;
        $this->ucrmApi = UcrmApi::create();
        $options = UcrmOptionsManager::create()->loadOptions();
        $this->unmsApi = $options->unmsLocalUrl !== null ? UnmsApi::create($logger) : null;
        $this->ucrmUri = (string) $options->ucrmPublicUrl;
        $this->routerOsApi = RouterOsApi::create($config);
    }

    /**
     * Entry point for main.php (automatic execution) and public.php (Sync menu).
     */
    public static function run(): bool
    {
        $logger = PluginLogManager::create();
        $logger->clearLog();

        try {
            (new self((new PluginConfigManager())->loadConfig(), $logger))->sync();
            $logger->appendLog('Synchronization finished / Sincronización finalizada');

            return true;
        } catch (\Exception $exception) {
            $logger->appendLog('Error: ' . $exception->getMessage());

            return false;
        }
    }

    public function sync(): void
    {
        $configErrors = self::validateConfig($this->config);
        foreach ($configErrors as $configError) {
            $this->logger->appendLog($configError);
        }
        if ($configErrors) {
            throw new ConfigurationException('Wrong plugin configuration / Configuración incorrecta del complemento');
        }
        if ($this->config['debugMode'] ?? false) {
            $this->logger->appendLog(sprintf('Router %s:%s, user %s', $this->config['mktip'], $this->config['apiport'] ?: '8728', $this->config['mktusr']));
        }

        $allQueues = $this->routerOsApi->print('/queue/simple');
        $usedNames = array_flip(array_column($allQueues, 'name'));
        $queuesByTarget = [];
        foreach (self::serviceQueues($allQueues) as $queue) {
            $queuesByTarget[self::targetKey((string) ($queue['target'] ?? ''))][] = $queue;
        }

        $toAdd = [];
        $toSet = [];
        foreach ($this->getServices() as $target => $service) {
            if (! isset($queuesByTarget[$target])) {
                $toAdd[] = $service;
                continue;
            }

            $queues = $queuesByTarget[$target];
            if (count($queues) > 1) {
                $this->logger->appendLog(sprintf('Duplicated queues for %s: %s', $service['target'], implode(' | ', array_column($queues, 'name'))));
            }
            foreach ($queues as $index => $queue) {
                // Only the first queue (the one Mikrotik uses) gets the UISP name, never a name already used: Mikrotik would reject the whole set
                $name = $queue['name'];
                if ($index === 0 && $service['queueName'] !== $name) {
                    if (isset($usedNames[$service['queueName']])) {
                        $this->logger->appendLog(sprintf('Queue "%s" not renamed to "%s": name already used by another queue', $name, $service['queueName']));
                    } else {
                        $name = $service['queueName'];
                    }
                }
                $attributes = array_intersect_key($service, array_flip(self::QUEUE_ATTRIBUTES));
                if ($name !== $queue['name'] || array_diff_assoc($attributes, array_intersect_key($queue, $attributes))) {
                    $toSet[] = [
                        '.id' => $queue['.id'],
                        'name' => $name,
                    ] + $attributes;
                }
            }
        }

        if ($this->config['addQueue'] ?? false) {
            $serviceQueueIds = array_column(self::serviceQueues($allQueues), '.id', 'name');
            $toCreate = [];
            foreach ($toAdd as $service) {
                $queue = [
                    'name' => $service['queueName'],
                    'target' => $service['target'],
                ] + array_intersect_key($service, array_flip(self::QUEUE_ATTRIBUTES));
                if (isset($serviceQueueIds[$service['queueName']])) {
                    // The service queue exists with another target (service IP changed): update it
                    $toSet[] = [
                        '.id' => $serviceQueueIds[$service['queueName']],
                    ] + $queue;
                } elseif (isset($usedNames[$service['queueName']])) {
                    $this->logger->appendLog(sprintf('Queue for %s not added: name "%s" already used by a parent or dynamic queue', $service['target'], $service['queueName']));
                } else {
                    $toCreate[] = $queue;
                }
            }
            $this->logErrors($this->routerOsApi->add('/queue/simple', $toCreate));
            $this->logger->appendLog(sprintf('Queues added / Colas agregadas: %d', count($toCreate)));
        }

        $this->logErrors($this->routerOsApi->set('/queue/simple', $toSet));
        $this->logger->appendLog(sprintf('Queues updated / Colas actualizadas: %d', count($toSet)));
    }

    public static function filterBySyncRanges(array $services, array $ranges): array
    {
        $result = [];
        foreach ($services as $service) {
            $ips = [];
            foreach ($ranges as $range) {
                foreach (explode(',', $service['target']) as $ip) {
                    if (self::ipInRange(strtok($ip, '/'), $range)) {
                        $ips[] = $ip;
                    }
                }
            }
            if ($ips) {
                $service['target'] = implode(',', array_unique($ips));
                $result[] = $service;
            }
        }

        return $result;
    }

    /**
     * Range formats used in Mikrotik address-lists: 10.0.0.1, 10.0.0.0/24, 10.0.0.1-10.0.0.50
     */
    public static function ipInRange(string $ip, string $range): bool
    {
        $ipLong = ip2long($ip);
        if (strpos($range, '-') !== false) {
            [$from, $to] = explode('-', $range, 2);

            return $ipLong >= ip2long($from) && $ipLong <= ip2long($to);
        }
        [$network, $bits] = explode('/', $range . '/32');
        $mask = (int) $bits === 0 ? 0 : -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === (ip2long($network) & $mask);
    }

    /**
     * Free version: first $limit services by service ID, keeping their order.
     */
    public static function limitServices(array $services, int $limit): array
    {
        $ids = array_column($services, 'id');
        sort($ids);
        $allowed = array_flip(array_slice($ids, 0, $limit));

        return array_values(array_filter($services, function (array $service) use ($allowed): bool {
            return isset($allowed[$service['id']]);
        }));
    }

    /**
     * Mikrotik simple queue values (upload/download) for one UISP service.
     * An empty override speed uses the plan speed.
     */
    public static function queueAttributes(array $service, ?array $plan, array $config, array &$warnings = []): array
    {
        $speeds = [];
        foreach (['upload', 'download'] as $direction) {
            $override = $service[$direction . 'SpeedOverride'] ?? null;
            $speed = ($service['trafficShapingOverrideEnabled'] ?? false) == true && $override !== null && $override !== ''
                ? $override
                : ($service[$direction . 'Speed'] ?? null);
            $speeds[] = $speed === null ? 0 : strval((float) $speed * 1000000);
        }
        $maxLimit = explode('/', implode('/', $speeds));

        $limitAtPercentages = explode('/', $config['limitAtPercentage']);
        $burstLimit = explode('/', $config['burstLimitPercentage']);
        $burstTime = explode('/', $config['burstTime']);

        // Service plan Data usage limit is used as queue priority
        $priority = $plan['dataUsageLimit'] ?? 8;
        if ($priority < 1 || $priority > 8) {
            $priority = '8';
        }

        $burstLimits = [
            $maxLimit[0] + ($maxLimit[0] * $burstLimit[0] / 100),
            $maxLimit[1] + ($maxLimit[1] * $burstLimit[1] / 100),
        ];

        $thresholds = [];
        foreach (['upload', 'download'] as $i => $direction) {
            $threshold = isset($plan[$direction . 'Burst']) ? strval((float) $plan[$direction . 'Burst'] * 1000) : 0;
            if (intval($threshold) != 0 && intval($burstLimits[$i]) < intval($threshold)) {
                // Burst-threshold higher than burst-limit: 15% under the limit
                $threshold = (intval($burstLimits[$i]) - (intval($burstLimits[$i]) * 15 / 100));
                $warnings[] = 'has ' . $direction . ' burst-threshold higher than burst-limit, check burst config on plugin and service plan';
            }
            $thresholds[] = $threshold;
        }

        return [
            'max-limit' => $maxLimit[0] . '/' . $maxLimit[1],
            'limit-at' => $maxLimit[0] * $limitAtPercentages[0] / 100 . '/' . $maxLimit[1] * $limitAtPercentages[1] / 100,
            'priority' => $priority . '/' . $priority,
            'burst-limit' => $burstLimits[0] . '/' . $burstLimits[1],
            'burst-threshold' => $thresholds[0] . '/' . $thresholds[1],
            'burst-time' => $burstTime[0] . 's' . '/' . $burstTime[1] . 's',
        ];
    }

    /**
     * @return string[] configuration errors, empty when valid
     */
    public static function validateConfig(array $config): array
    {
        $rules = [
            'burstLimitPercentage' => ['Burst Limit Percentage', 0, 99],
            'burstTime' => ['Burst Time', 1, 99],
            'limitAtPercentage' => ['LimitAt percentage', 0, 99],
        ];

        $errors = [];
        foreach ($rules as $key => [$label, $min, $max]) {
            if (! preg_match('~^(\d{1,2})/(\d{1,2})\z~', (string) ($config[$key] ?? ''), $values)) {
                $errors[] = $label . ' should be set in format UU/DD, U= Upload value, D= Download value';
            } elseif ($values[1] < $min || $values[2] < $min || $values[1] > $max || $values[2] > $max) {
                $errors[] = $label . ' should be set between ' . $min . ' and ' . $max;
            }
        }

        return $errors;
    }

    /**
     * Same IPs in any order are the same target.
     */
    public static function targetKey(string $target): string
    {
        $ips = explode(',', $target);
        sort($ips);

        return implode(',', $ips);
    }

    /**
     * Queues this plugin may manage: never dynamic queues (PPPoE, hotspot...) nor parent queues, even if their target matches a service IP.
     */
    public static function serviceQueues(array $queues): array
    {
        $parents = array_flip(array_column($queues, 'parent'));

        return array_values(array_filter($queues, function (array $queue) use ($parents): bool {
            return ($queue['dynamic'] ?? 'false') !== 'true' && ! isset($parents[$queue['name'] ?? '']);
        }));
    }

    /**
     * Services to sync indexed by target, with their queue name and queue attributes.
     */
    private function getServices(): array
    {
        $services = $this->ucrmApi->get('clients/services', [
            'statuses' => self::SYNCED_STATUSES,
        ]);
        $services = $this->addAddresses($services);

        $syncRanges = $this->getSyncRanges();
        if ($syncRanges) {
            $services = self::filterBySyncRanges($services, $syncRanges);
        }

        $services = $this->addQueueNames($services);
        $limited = self::limitServices($services, self::SERVICES_LIMIT);
        if (count($limited) < count($services)) {
            $this->logger->appendLog(sprintf(
                'Free version limit: %d of %d services synced / Límite de la versión gratuita: %d de %d servicios sincronizados',
                count($limited),
                count($services),
                count($limited),
                count($services)
            ));
            $this->logger->appendLog(self::PRO_NOTICE);
        }

        $plans = array_column($this->ucrmApi->get('service-plans'), null, 'id');
        $result = [];
        foreach ($limited as $service) {
            $warnings = [];
            $service += self::queueAttributes($service, $plans[$service['servicePlanId']] ?? null, $this->config, $warnings);
            foreach ($warnings as $warning) {
                $this->logger->appendLog('Service ID: ' . $service['id'] . ' ' . $warning);
            }
            $result[self::targetKey($service['target'])] = $service;
        }

        return $result;
    }

    /**
     * Adds 'target' (comma separated IPs) to each service, services without IP are skipped and logged.
     */
    private function addAddresses(array $services): array
    {
        $siteIps = $this->unmsApi ? $this->getSiteIps() : [];

        $result = [];
        foreach ($services as $service) {
            if ($this->unmsApi) {
                $ips = $siteIps[$service['unmsClientSiteId'] ?? ''] ?? [];
            } else { // UCRM without UNMS
                $range = $service['ipRanges'][0] ?? null;
                $ips = $range ? [strpos($range, '/') ? $range : $range . '/32'] : [];
            }

            if ($ips) {
                $service['target'] = implode(',', $ips);
                $result[] = $service;
            } else {
                $this->logger->appendLog(sprintf('Service ID: %s has no IP address set, check on %sclient/service/%s', $service['id'], $this->ucrmUri, $service['id']));
            }
        }

        return $result;
    }

    /**
     * UNMS site ID => device IPs (as /32), in UNMS devices order.
     */
    private function getSiteIps(): array
    {
        $siteIps = [];
        foreach ($this->unmsApi->get('devices') as $device) {
            $siteId = $device['identification']['site']['id'] ?? null;
            if ($siteId === null) {
                $this->logger->appendLog(sprintf('Device ID: %s with MAC address: %s has no SITE assigned', $device['identification']['id'], $device['identification']['mac']));
            } elseif (isset($device['ipAddress'])) {
                $siteIps[$siteId][] = strtok($device['ipAddress'], '/') . '/32';
            }
        }

        return $siteIps;
    }

    private function addQueueNames(array $services): array
    {
        $servicesByClient = [];
        foreach ($services as $service) {
            $servicesByClient[$service['clientId']][] = $service;
        }

        $result = [];
        foreach ($this->ucrmApi->get('clients', [
            'isLead' => 0,
            'isArchived' => 0,
        ]) as $client) {
            foreach ($servicesByClient[$client['id']] ?? [] as $service) {
                if ($client['clientType'] == 1) {
                    $service['queueName'] = $client['firstName'] . ' ' . $client['lastName'] . ' - Service ID:' . $service['id'];
                } elseif ($client['clientType'] == 2) {
                    $service['queueName'] = $client['companyName'] . ' - Service ID: ' . $service['id'];
                } else {
                    $service['queueName'] = 'Service ID: ' . $service['id'];
                }
                $result[] = $service;
            }
        }

        return $result;
    }

    /**
     * Addresses of the sync_with_ucrm address-list: when present, only those IPs are synced.
     */
    private function getSyncRanges(): array
    {
        $ranges = $this->routerOsApi->printwhere('/ip/firewall/address-list/print', ['?list=sync_with_ucrm', '?disabled=no']);

        return array_column($ranges, 'address');
    }

    private function logErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->logger->appendLog($error);
        }
    }
}
