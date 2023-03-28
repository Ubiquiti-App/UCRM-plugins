<?php

declare(strict_types=1);


namespace UcrmRouterOs\Service;

use Nette\Utils\Strings;
use Ubnt\UcrmPluginSdk\Exception\ConfigurationException;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

class Suspender
{
    private const BLOCKED_USERS_LIST = 'BLOCKED_USERS';

    private const COMMENT_SIGNATURE = 'ucrm_';

    private const SERVICE_STATUS_ACTIVE = 3;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    /**
     * @var UnmsApi
     */
    private $unmsApi;

    /**
     * @var RouterOsApi
     */
    private $routerOsApi;

    /**
     * @var string[]
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->ucrmApi = UcrmApi::create();
        $this->unmsApi = UnmsApi::create($config);
        $this->routerOsApi = RouterOsApi::create($config);
    }

    public function suspend(): void
    {
        if (! $this->validateConfig($this->config)) {
            throw new ConfigurationException('Missing value in plugin configuration.');
        }

        $suspensionPageIp = $this->config['suspensionPageIp'];
        $suspensionPagePort = $this->config['suspensionPagePort'];

        $this->syncNatRules($suspensionPageIp, (int) $suspensionPagePort);
        $this->syncFilterRules($suspensionPageIp);
        $this->syncAddressList();
    }

    private function syncNatRules(string $suspensionPageIp, int $suspensionPagePort): void
    {
        $natDstJumpRules = [
            'first_dstnat',
            'general_dstnat',
            'last_dstnat',
        ];

        $natSrcJumpRules = [
            'first_srcnat',
            'general_srcnat',
            'range_srcnat',
            'last_srcnat',
        ];

        $rules = [];

        // add jump rules
        foreach ($natDstJumpRules as $jumpRule) {
            $rules[] = [
                'chain' => 'dstnat',
                'action' => 'jump',
                'comment' => $jumpRule,
                'jump-target' => sprintf('%s%s', self::COMMENT_SIGNATURE, $jumpRule),
                'out-interface' => '',
            ];
        }
        foreach ($natSrcJumpRules as $jumpRule) {
            $rules[] = [
                'chain' => 'srcnat',
                'action' => 'jump',
                'comment' => $jumpRule,
                'jump-target' => sprintf('%s%s', self::COMMENT_SIGNATURE, $jumpRule),
                'out-interface' => '',
            ];
        }

        $rules[] = [
            'chain' => sprintf('%s%s', self::COMMENT_SIGNATURE, 'first_dstnat'),
            'action' => 'dst-nat',
            'src-address-list' => self::BLOCKED_USERS_LIST,
            'dst-port' => 80,
            'to-ports' => $suspensionPagePort,
            'protocol' => 'tcp',
            'comment' => 'blocked_user_redirect',
            'to-addresses' => $suspensionPageIp,
            'out-interface' => '',
        ];

        $this->setNat($rules);
    }

    private function syncFilterRules(string $serverIp): void
    {
        $rules = [
            [
                'chain' => 'input',
                'src-address' => $serverIp,
                'comment' => 'accept_input',
                'action' => 'accept',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],
            [
                'chain' => 'forward',
                'src-address' => $serverIp,
                'comment' => 'accept_forward',
                'action' => 'accept',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],

            [
                'chain' => 'forward',
                'comment' => 'forward_first',
                'jump-target' => 'ucrm_forward_first',
                'action' => 'jump',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],

            [
                'chain' => 'forward',
                'comment' => 'forward_general',
                'jump-target' => 'ucrm_forward_general',
                'action' => 'jump',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],

            [
                'chain' => 'forward',
                'comment' => 'forward_drop',
                'jump-target' => 'ucrm_forward_drop',
                'action' => 'jump',
                'dst-port' => '',
                'protocol' => '',
                'src-address-list' => '',
                'dst-address' => '',
            ],
            [
                'chain' => 'ucrm_forward_general',
                'comment' => 'blocked_users_allow_dns',
                'protocol' => 'udp',
                'dst-port' => 53,
                'src-address-list' => self::BLOCKED_USERS_LIST,
                'action' => 'accept',
                'src-address' => '',
                'dst-address' => '',
            ],
            [
                'chain' => 'ucrm_forward_drop',
                'comment' => 'blocked_users_drop',
                'src-address-list' => self::BLOCKED_USERS_LIST,
                'dst-address' => '!' . $serverIp,
                'action' => 'drop',
                'src-address' => '',
                'dst-port' => '',
                'protocol' => '',
            ],
        ];

        $this->setFilterRules($rules);
    }

    private function syncAddressList(): void
    {
        $clientSiteIds = [];

        foreach ($this->findServicesToSuspend() as $service) {
            if ($service['unmsClientSiteId'] ?? false) {
                $clientSiteIds[] = $service['unmsClientSiteId'];
            }
        }

        $this->setIpFirewallAddressList($this->findIpsFromNetwork($clientSiteIds));
    }

    private function setNat(array $content): void
    {
        $section = '/ip/firewall/nat';

        $attrs = [
            'chain',
            'action',
            'comment',
            'jump-target',
            'dst-address',
            'src-address',
            'to-addresses',
            'to-ports',
            'out-interface',
        ];

        $remoteSectionList = $this->getSectionList($section, $attrs);

        $remoteList = $this->createIndex($remoteSectionList, $attrs);
        $localList = $this->createIndex($content, $attrs);
        $toRemove = array_diff_key($remoteList, $localList);
        $toAdd = array_diff_key($localList, $remoteList);

        $this->routerOsApi->remove($section, array_column($toRemove, '.id'));
        $this->routerOsApi->add($section, $toAdd);
    }

    private function setFilterRules(array $content): void
    {
        $section = '/ip/firewall/filter';
        $attrs = [
            'chain',
            'comment',
            'src-address-list',
            'dst-address',
            'action',
            'src-address',
            'dst-port',
            'protocol',
        ];

        $remoteList = $this->createIndex($this->getSectionList($section, $attrs), $attrs);
        $localList = $this->createIndex($content, $attrs);

        $toRemove = array_diff_key($remoteList, $localList);
        $toAdd = array_diff_key($localList, $remoteList);

        $this->routerOsApi->remove($section, array_column($toRemove, '.id'));
        $this->routerOsApi->add($section, $toAdd);
    }

    private function findServicesToSuspend(): array
    {
        return $this->ucrmApi->get(
            'clients/services',
            [
                'statuses' => [self::SERVICE_STATUS_ACTIVE],
            ]
        );
    }

    private function findIpsFromNetwork(array $clientSiteIds): array
    {
        $ipAddresses = [];
        foreach ($clientSiteIds as $clientSiteId) {
            $clientSiteIps = $this->unmsApi->get(
                'devices/ips',
                [
                    'siteId' => $clientSiteId,
                ]
            );

            foreach ($clientSiteIps as $clientSiteIp) {
                $ipAddresses[] = $clientSiteIp;
            }
        }

        return array_unique($ipAddresses);
    }

    private function setIpFirewallAddressList(array $ipAddresses): void
    {
        $attributes = ['list', 'address', 'comment'];
        $routerList = $this->createIndex($this->findAndFilterUcrmIpAddressListOnRouter(), $attributes);
        $crmList = $this->createIndex($this->createCrmIpAddressLists($ipAddresses), $attributes);

        $this->removeAddress(array_diff_key($routerList, $crmList));
        $this->addAddress(array_diff_key($crmList, $routerList));
    }

    private function removeAddress(array $addresses): array
    {
        return $this->routerOsApi->remove('/ip/firewall/address-list', array_column($addresses, '.id'));
    }

    private function addAddress(array $addresses): void
    {
        $this->routerOsApi->add('/ip/firewall/address-list', $addresses);
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

    private function createCrmIpAddressLists(array $ipAddresses): array
    {
        $addressListRows = [];
        foreach ($ipAddresses as $ipAddress) {
            $addressListRows[] = [
                'list' => self::BLOCKED_USERS_LIST,
                'address' => $ipAddress,
                'comment' => 'blocked_users',
            ];
        }

        return $addressListRows;
    }

    private function findAndFilterUcrmIpAddressListOnRouter(): array
    {
        $filtered = [];
        foreach ($this->routerOsApi->print('/ip/firewall/address-list') as $address) {
            if (
                array_key_exists('comment', $address)
                && Strings::startsWith($address['comment'], self::COMMENT_SIGNATURE)
            ) {
                $address['comment'] = substr($address['comment'], strlen(self::COMMENT_SIGNATURE));
                $filtered[] = $address;
            }
        }

        return $filtered;
    }

    private function getSectionList(string $section, array $attributes): array
    {
        $data = $this->getRawSectionList($section, $attributes);

        $filtered = [];
        foreach ($data as $row) {
            if (array_key_exists('comment', $row) && Strings::startsWith($row['comment'], self::COMMENT_SIGNATURE)) {
                $row['comment'] = substr($row['comment'], strlen(self::COMMENT_SIGNATURE));
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    private function getRawSectionList(string $section, array $attributes = []): array
    {
        $result = $this->routerOsApi->print(
            $section,
            empty($attributes) ? [] : [
                '.proplist' => sprintf('.id,%s', implode(',', $attributes)),
            ]
        );

        return is_array($result) ? $result : [];
    }

    private function validateConfig(array $config): bool
    {
        $configAttributes = [
            'mikrotikIpAddress',
            'mikrotikUserName',
            'mikrotikPassword',
            'suspensionPageIp',
            'suspensionPagePort',
        ];

        foreach ($configAttributes as $configAttribute) {
            if (! array_key_exists($configAttribute, $config)) {
                return false;
            }
        }

        return true;
    }
}
