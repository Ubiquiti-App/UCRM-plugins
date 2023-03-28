<?php

declare(strict_types=1);

namespace MikrotikQueueSync;

use DateTime;
use Nette\Utils\Strings;
use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;

require __DIR__ . '/../vendor/autoload.php';

class Synchronizer
{
    private const COMMENT_SIGNATURE = 'ucrm_mktsync_';

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var pluginConfigManager
     */
    private $pluginConfigManager;

    /**
     * @var UnmsApi
     */
    private $unmsApi; //Enable only for UNMS v1

    /**
     * @var RouterOsApi
     */
    private $routerOsApi;

    /**
     * @var SyncAddressList
     */
    private $syncAddressList;

    /**
     * @var RemoveEnded
     */
    private $removeEnded;

    public function __construct()
    {
        $this->ucrmApi = UcrmApi::create();
        $this->logger = PluginLogManager::create();
        $this->pluginConfigManager = PluginConfigManager::create();
        if ((new UcrmOptionsManager())->loadOptions()->unmsLocalUrl !== null) {
            $this->unmsApi = UnmsApi::create($this->logger); //Enable only for UNMS v1
            $this->ucrmVersion = 3;
        } else {
            $this->ucrmVersion = 2; // UCRM v2
        }
        $config = (new PluginConfigManager())->loadConfig();
        $this->ip_in_range = Ip_In_Range::create();
        $this->mktDevices = 0;
        $this->routerOsApi = RouterOsApi::create($this->logger, $this->mktDevices);
        $this->sumDownloadLimitAt = $this->sumUploadLimitAt = $this->sumDownloadVendido = $this->sumUploadVendido = 0;
        $this->timeStamp = new DateTime();
    }

    public function sync(): void
    {
        $this->logger->appendLog('Running Plugin ' . $this->timeStamp->format('d-m-Y H:i:s'));
        $this->mktDevices = 1;
        $deviceNum = 0;
        do {
            // Retrieve UCRM Config.
            $this->optionsData = $this->pluginConfigManager->loadConfig();
            $this->debug = $this->optionsData['debugMode'];

            if (! $this->validateConfig($this->optionsData)[0]) {
                $this->logger->appendLog('Missing value in plugin configuration');
                foreach ($this->validateConfig($this->optionsData) as $Exception) {
                    $this->logger->appendLog((string) $Exception);
                }
                throw new ConfigurationException('Missing value in plugin configuration.');
            }

            $this->logger->appendLog('Synchronization started');

            if ($this->debug) {
                $this->logger->appendLog('Mikrotik Connect Data obtained in importer');
                $this->logger->appendLog('IP:' . $this->optionsData['mktip']);
                $this->logger->appendLog('Usuario:' . $this->optionsData['mktusr']);
                $this->logger->appendLog('Password:' . $this->optionsData['mktpass']);
            }

            /******************************************************************************
            *                               SYNC SERVICES                                 *
            ******************************************************************************/

            $this->servicePlans = $this->ucrmApi->get('service-plans'); //Get service Plans for Burst Management

            /*------------------ GET MIKROTIK ADDRESS-LIST SYNC LIST --------------------*/
            $syncList = $this->findAndFilterNetworksToSyncOnRouter($deviceNum);

            /*------------------------ GET MIKROTIK QUEUE LIST --------------------------*/

            $attrsmktQueueList = [
                'target',
                'max-limit',
                'limit-at',
                'priority',
                'burst-limit',
                'burst-threshold',
                'burst-time',
            ];

            $mktQueueList = $this->createIndex($this->getSectionList($deviceNum, '/queue/simple', $attrsmktQueueList), $attrsmktQueueList);

            /*------------------------ GET UCRM SERVICES LIST --------------------------*/

            $this->ucrmServiceList = $this->createIndex($this->getUcrmServiceList($attrsmktQueueList), $attrsmktQueueList);

            /*--------------- COMPARE DIFFERENCES BETWEEN UCRM & MKT -------------------*/

            $remoteLocalDiffList = array_diff_key($this->ucrmServiceList, $mktQueueList);

            /*------------------------ GET UCRM SERVICES LIST --------------------------*/

            $attrsForAddOrModifyList = [
                'target',
            ];

            $queueAddOrModifyList = $this->createIndex($remoteLocalDiffList, $attrsForAddOrModifyList);

            /*--------------------- GET ARRAY WITH QUEUES TO ADD ----------------------*/
            $queueAddList = array_diff_key($queueAddOrModifyList, $this->createIndex($this->getSectionList($deviceNum, '/queue/simple', $attrsForAddOrModifyList), $attrsForAddOrModifyList));
            if (! empty($syncList)) {
                $queueAddList = $this->filterQueueAddListWithSyncList($queueAddList, $syncList);
            }

            empty(! $queueAddList) ? $preparedQueueAddList = $this->prepareArrayToAdd($queueAddList) : [];

            (isset($preparedQueueAddList) && $this->optionsData['addQueue']) ? $this->routerOsApi->add($deviceNum, '/queue/simple', $preparedQueueAddList) : [];

            /*--------------------- GET ARRAY WITH QUEUES TO SET ----------------------*/
            $queueSetList = array_diff_key($queueAddOrModifyList, $queueAddList);

            empty(! $queueSetList) ? $preparedQueueSetList = $this->prepareArrayToSet($deviceNum, $queueSetList) : [];

            isset($preparedQueueSetList) ? $this->routerOsApi->set($deviceNum, '/queue/simple', $preparedQueueSetList) : [];

            /******************************************************************************
            *                            FINAL OF THE CODE                                *
            ******************************************************************************/

            $this->logger->appendLog('Synchronization correctly ended / Sincronizado Correctamente');
            $this->logger->appendLog(sprintf('Sumatoria de Download LimitAt: %s', $this->formatSpeedForStats($this->sumDownloadLimitAt)));
            $this->logger->appendLog(sprintf('Sumatoria de Upload LimitAt: %s', $this->formatSpeedForStats($this->sumUploadLimitAt)));
            $this->logger->appendLog(sprintf('Sumatoria de DownloadVendido: %s', $this->formatSpeedForStats($this->sumDownloadVendido)));
            $this->logger->appendLog(sprintf('Sumatoria de UploadVendido: %s', $this->formatSpeedForStats($this->sumUploadVendido)));

            //Incremet to move on the next device (If exists)
            $deviceNum++;
        } while ($deviceNum < $this->mktDevices);
    }

    private function getUcrmServiceList($attrs): array
    {
        $ucrmServiceList = $this->ucrmApi->get('clients/services', [
            'statuses[0]' => 1,
            'statuses[1]' => 0,
            'statuses[2]' => 3,
            'statuses[3]' => 4,
            'statuses[4]' => 6,
        ]);
        $ucrmServiceList = $this->formatUcrmListIpAddress($ucrmServiceList);
        $ucrmServiceList = $this->formatUcrmListMaxLimit($ucrmServiceList);
        $ucrmServiceList = $this->formatUcrmListLimitAt($ucrmServiceList);
        $ucrmServiceList = $this->formatUcrmListPriority($ucrmServiceList);
        $ucrmServiceList = $this->formatUcrmListBurstLimit($ucrmServiceList);
        $ucrmServiceList = $this->formatUcrmListBurstThreshold($ucrmServiceList);
        $ucrmServiceList = $this->formatUcrmListBurstTime($ucrmServiceList);

        return $ucrmServiceList;
    }

    private function formatUcrmListIpAddress($ucrmServiceList): array
    {
        if ($this->ucrmVersion == 2) { //IF UCRM V2
            foreach ($ucrmServiceList as $ucrmService) {
                if (isset($ucrmService['ipRanges'][0])) {
                    (strpos($ucrmService['ipRanges'][0], '/')) ? $ucrmService['target'] = $ucrmService['address'] = $ucrmService['ipRanges'][0] : $ucrmService['target'] = $ucrmService['address'] = $ucrmService['ipRanges'][0] . '/32';
                    $ucrmFormatedList[] = $ucrmService;
                } else {
                    $this->logger->appendLog('Service ID: ' . $ucrmService['id'] . ' has no ip address set');
                }
            }
            return $ucrmFormatedList;
        }   // IF UCRM V3 - UNMS V1
        foreach ($ucrmServiceList as $ucrmService) {
            if (isset($ucrmService['unmsClientSiteId'])) {
                $clientSiteIps = $this->unmsApi->get(
                    'devices/ips',
                    [
                        'siteId' => $ucrmService['unmsClientSiteId'],
                    ]
                );
                if (isset($clientSiteIps[0])) {
                    (strpos($clientSiteIps[0], '/')) ? $ucrmService['target'] = $ucrmService['address'] = $clientSiteIps[0] : $ucrmService['target'] = $ucrmService['address'] = $clientSiteIps[0] . '/32';
                    $ucrmFormatedList[] = $ucrmService;
                } else {
                    $this->logger->appendLog('Service ID: ' . $ucrmService['id'] . ' has no ip address set');
                }
            }
        }
        return $ucrmFormatedList;
    }

    private function formatUcrmListMaxLimit($ucrmServiceList): array
    {
        foreach ($ucrmServiceList as $ucrmService) {
            $ucrmService['max-limit'] = $this->formatSpeedForMikrotik(($ucrmService['uploadSpeed'])) . '/' . $this->formatSpeedForMikrotik(($ucrmService['downloadSpeed']));
            $this->sumUploadVendido += $this->formatSpeedForMikrotik(($ucrmService['uploadSpeed']));
            $this->sumDownloadVendido += $this->formatSpeedForMikrotik(($ucrmService['downloadSpeed']));
            $ucrmFormatedList[] = $ucrmService;
        }
        return $ucrmFormatedList;
    }

    private function formatUcrmListLimitAt($ucrmServiceList): array
    {
        $limitAtPercentages = explode('/', $this->optionsData['limitAtPercentage']);
        foreach ($ucrmServiceList as $ucrmService) {
            $ucrmService['limit-at'] = $this->formatSpeedForMikrotik(($ucrmService['uploadSpeed'])) * $limitAtPercentages[0] / 100 . '/' . $this->formatSpeedForMikrotik(($ucrmService['downloadSpeed'])) * $limitAtPercentages[1] / 100;
            $this->sumUploadLimitAt += $this->formatSpeedForMikrotik(($ucrmService['uploadSpeed'])) * $limitAtPercentages[0] / 100;
            $this->sumDownloadLimitAt += $this->formatSpeedForMikrotik(($ucrmService['downloadSpeed'])) * $limitAtPercentages[1] / 100;
            $ucrmFormatedList[] = $ucrmService;
        }
        return $ucrmFormatedList;
    }

    private function formatUcrmListPriority($ucrmServiceList): array
    {
        foreach ($ucrmServiceList as $ucrmService) {
            (isset($this->servicePlans[array_search($ucrmService['servicePlanId'], array_column($this->servicePlans, 'id'))]['dataUsageLimit'])) ? $priority = $this->servicePlans[array_search($ucrmService['servicePlanId'], array_column($this->servicePlans, 'id'))]['dataUsageLimit'] : $priority = 8; //Using Service DataUsageLimit as Priority
            if ($priority < 1 || $priority > 8) {
                $priority = '8';
            }
            $ucrmService['priority'] = $priority . '/' . $priority;
            $ucrmFormatedList[] = $ucrmService;
        }
        return $ucrmFormatedList;
    }

    private function formatUcrmListBurstLimit($ucrmServiceList): array
    {
        $burstLimit = explode('/', $this->optionsData['burstLimitPercentage']);
        foreach ($ucrmServiceList as $ucrmService) {
            $ucrmService['burst-limit'] = $this->formatSpeedForMikrotik($ucrmService['uploadSpeed'] + ($ucrmService['uploadSpeed'] * $burstLimit[0] / 100)) . '/' . $this->formatSpeedForMikrotik($ucrmService['downloadSpeed'] + ($ucrmService['downloadSpeed'] * $burstLimit[1] / 100));
            $ucrmFormatedList[] = $ucrmService;
        }
        return $ucrmFormatedList;
    }

    private function formatUcrmListBurstThreshold($ucrmServiceList): array
    {
        foreach ($ucrmServiceList as $ucrmService) {
            (isset($this->servicePlans[array_search($ucrmService['servicePlanId'], array_column($this->servicePlans, 'id'))]['uploadBurst'])) ? $uploadBurstThreshold = $this->formatSpeedForMikrotikBurst($this->servicePlans[array_search($ucrmService['servicePlanId'], array_column($this->servicePlans, 'id'))]['uploadBurst']) : $uploadBurstThreshold = 0;
            (isset($this->servicePlans[array_search($ucrmService['servicePlanId'], array_column($this->servicePlans, 'id'))]['downloadBurst'])) ? $downloadBurstThreshold = $this->formatSpeedForMikrotikBurst($this->servicePlans[array_search($ucrmService['servicePlanId'], array_column($this->servicePlans, 'id'))]['downloadBurst']) : $downloadBurstThreshold = 0;
            $ucrmService['burst-threshold'] = $uploadBurstThreshold . '/' . $downloadBurstThreshold;
            $ucrmFormatedList[] = $ucrmService;
        }
        return $ucrmFormatedList;
    }

    private function formatUcrmListBurstTime($ucrmServiceList): array
    {
        $burstTime = explode('/', $this->optionsData['burstTime']);
        foreach ($ucrmServiceList as $ucrmService) {
            $ucrmService['burst-time'] = $burstTime[0] . 's' . '/' . $burstTime[1] . 's';
            $ucrmFormatedList[] = $ucrmService;
        }
        return $ucrmFormatedList;
    }

    private function prepareArrayToAdd($addList): array
    {
        $attrs = [
            'target',
            'max-limit',
            'limit-at',
            'priority',
            'burst-limit',
            'burst-threshold',
            'burst-time',
        ];

        foreach ($addList as $addListRow) {
            $toAddRow['name'] = $this->getQueueNameFromUcrm($addListRow['clientId'], $addListRow['id']);
            foreach ($attrs as $attribute) {
                $toAddRow[$attribute] = $addListRow[$attribute];
            }
            $readyToAddList[] = $toAddRow;
        }
        return $readyToAddList;
    }

    private function prepareArrayToSet($deviceNum, $setList): array
    {
        $attrs = [
            'max-limit',
            'limit-at',
            'priority',
            'burst-limit',
            'burst-threshold',
            'burst-time',
        ];

        foreach ($setList as $setListRow) {
            $toSetRow['.id'] = $this->getQueueId($deviceNum, $setListRow['target']);
            $toSetRow['name'] = $this->getQueueNameFromUcrm($setListRow['clientId'], $setListRow['id']);
            foreach ($attrs as $attribute) {
                $toSetRow[$attribute] = $setListRow[$attribute];
            }
            $readyToSetList[] = $toSetRow;
        }
        return $readyToSetList;
    }

    private function formatSpeedForMikrotik(float $speed): string
    {
        $speed = $speed * 1000000; //MB to bytes
        return strval($speed);
    }

    private function formatSpeedForMikrotikBurst(float $speed): string
    {
        $speed = $speed * 1000; //KB to bytes
        return strval($speed);
    }

    private function formatSpeedForStats(float $speed): string
    {
        $speed = round($speed, 0);
        $count = 0;
        while ($speed > 1000) {
            $speed = $speed / 1000;
            $count++;
        }
        switch ($count) {
            case 0:
                return strval($speed) . 'B';
            case 1:
                return strval($speed) . 'KB';
            case 2:
                return strval($speed) . 'MB';
            case 3:
                return strval($speed) . 'GB';
            case 4:
                return strval($speed) . 'TB';
            default:
                return strval($speed) . ' ¿¿¿WTF???';
        }
    }

    private function createIndex(array $arr, array $attrs): array
    {
        $index = [];
        foreach ($arr as $row) {
            $key = $this->createIndexKey($row, $attrs);
            $index[$key] = $row;
        }

        return $index;
    }

    private function createIndexKey(array $item, array $attrs): string
    {
        $res = '';
        foreach ($attrs as $attr) {
            $res .= '_' . (array_key_exists($attr, $item) ? $item[$attr] : '');
        }

        return $res;
    }

    private function getSectionList(int $devNumber, string $section, array $attributes): array
    {
        $data = $this->getRawSectionList($devNumber, $section, $attributes);

        $filtered = [];
        foreach ($data as $row) {
            $filtered[] = $row;
        }
        return $filtered;
    }

    private function getRawSectionList(int $devNumber, string $section, array $attributes = []): array
    {
        $result = $this->routerOsApi->print(
            $devNumber,
            $section,
            empty($attributes) ? [] : [
                '.proplist' => sprintf('.id,%s', implode(',', $attributes)),
            ]
        );

        return is_array($result) ? $result : [];
    }

    private function getQueueId(int $deviceNum, string $ipAddress): string
    {
        $result = $this->routerOsApi->wr(
            $deviceNum,
            '/queue/simple/print',
            '?target=' . $ipAddress
        );
        if (empty($result)) {
            return 'NaN';
        }
        return is_string($result[0]['.id']) ? $result[0]['.id'] : [];
    }

    private function getQueueNameFromUcrm(int $clientId, int $ucrmServiceId): string
    {
        $clientInfo = $this->ucrmApi->get(sprintf('clients/%s', $clientId));
        if ($clientInfo['clientType'] == 1) {
            $queueName = $clientInfo['firstName'] . ' ' . $clientInfo['lastName'] . ' - Service ID:' . $ucrmServiceId;
        } elseif ($clientInfo['clientType'] == 2) {
            $queueName = $clientInfo['companyName'] . ' - Service ID: ' . $ucrmServiceId;
        } else {
            $queueName = 'Service ID: ' . $ucrmServiceId;
        }
        return $queueName;
    }

    private function validateConfig(array $config): array
    {
        $valid = [];
        $valid[0] = false;

        if (preg_match('/^\d{1,2}\W\d{1,2}\z/', $config['burstLimitPercentage'])) {
            if (explode('/', $config['burstLimitPercentage'])[0] < 100 && explode('/', $config['burstLimitPercentage'])[0] >= 0 && explode('/', $config['burstLimitPercentage'])[1] < 100 && explode('/', $config['burstLimitPercentage'])[1] >= 0) {
                $valid[0] = true;
            } else {
                $valid[0] = false;
                $valid[] = 'Burst Limit Percentage should be set between 0 and 99';
            }
        } else {
            $valid[0] = false;
            $valid[] = 'Burst Limit Percentage should be set in format UU/DD, U= Upload percentage, D= Download percentage';
        }

        if ($valid[0]) {
            if (preg_match('/^\d{1,2}\W\d{1,2}\z/', $config['burstTime'])) {
                if (explode('/', $config['burstTime'])[0] < 100 && explode('/', $config['burstTime'])[0] > 0 && explode('/', $config['burstTime'])[1] < 100 && explode('/', $config['burstTime'])[1] > 0) {
                    if (explode('/', $config['burstTime'])[0] == 0 && explode('/', $config['burstLimitPercentage'])[0] != 0) {
                        $valid[0] = false;
                        $valid[] = ' Upload Burst Time can\'t be 0 if Upload Burst Limit is configured';
                    }
                    if (explode('/', $config['burstTime'])[1] == 0 && explode('/', $config['burstLimitPercentage'])[1] != 0) {
                        $valid[0] = false;
                        $valid[] = ' Download Burst Time can\'t be 0 if Download Burst Limit is configured';
                    }
                } else {
                    $valid[0] = false;
                    $valid[] = 'Burst Time should be set between 1 and 99';
                }
            } else {
                $valid[0] = false;
                $valid[] = 'Burst Time should be set in format UU/DD, U= Upload Burst time, D= Download Burst time';
            }
        }

        if ($valid[0]) {
            if (preg_match('/^\d{1,2}\W\d{1,2}\z/', $config['limitAtPercentage'])) {
                if (explode('/', $config['limitAtPercentage'])[0] < 100 && explode('/', $config['limitAtPercentage'])[0] >= 0 && explode('/', $config['limitAtPercentage'])[1] < 100 && explode('/', $config['limitAtPercentage'])[1] >= 0) {
                } else {
                    $valid[0] = false;
                    $valid[] = 'LimitAt percentage should be set between 1 and 99';
                }
            } else {
                $valid[0] = false;
                $valid[] = 'LimitAt should be set in format UU/DD, U= Upload LimitAt percentage, D= Download LimitAt percentage time';
            }
        }

        return $valid;
    }

    private function findAndFilterNetworksToSyncOnRouter(int $deviceNum): array
    {
        $filtered = [];
        foreach ($this->routerOsApi->print($deviceNum, '/ip/firewall/address-list') as $address) {
            if (
                array_key_exists('list', $address)
                && Strings::startsWith($address['list'], 'sync_with_ucrm')
            ) {
                $filtered[] = $address['address'];
            }
        }
        return $filtered;
    }

    private function filterQueueAddListWithSyncList(array $queueToAdd, array $syncList): array
    {
        $filtered = [];
        foreach ($syncList as $syncRange) {
            (strpos($syncRange, '/')) ? [] : $syncRange = $syncRange . '/32';
            foreach ($queueToAdd as $queue) {
                if ($this->ip_in_range->ip_in_range(substr($queue['address'], 0, -3), $syncRange)) {
                    $filtered[] = $queue;
                }
            }
        }
        return $filtered;
    }
}
