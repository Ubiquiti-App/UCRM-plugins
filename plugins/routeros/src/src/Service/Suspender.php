<?php

declare(strict_types=1);


namespace UcrmRouterOs\Service;

use Ds\Set;
use Nette\Utils\Strings;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

class Suspender
{
    private const BLOCKED_USERS_LIST = 'BLOCKED_USERS';
    private const COMMENT_SIGNATURE = 'ucrm_';

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

    public function __construct()
    {
        $this->ucrmApi = UcrmApi::create();
        $this->unmsApi = UnmsApi::create();
        $this->routerOsApi = RouterOsApi::create();
    }

    public function suspend(): void
    {
        $clientSiteIds = [];
        foreach ($this->findServicesToSuspend() as $service) {
            if ($service['unmsClientSiteId'] ?? false) {
                $clientSiteIds[] = $service['unmsClientSiteId'];
            }
        }

        $this->setIpFirewallAddressList($this->findIpsFromNetwork($clientSiteIds));
    }

    private function findServicesToSuspend(): array
    {
        return $this->ucrmApi->get(
            'clients/services',
            [
                'statuses' => [3],
            ]
        );
    }

    private function findIpsFromNetwork(array $clientSiteIds): Set
    {
        $ipAddressses = new Set();
        foreach ($clientSiteIds as $clientSiteId) {
            $clientSiteIps = $this->unmsApi->get(
                'devices/ips',
                [
                    'siteId' => $clientSiteId,
                ]
            );

            $ipAddressses->add(...$clientSiteIps);
        }

        return $ipAddressses;
    }

    private function setIpFirewallAddressList(Set $ipAddresses): void
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

    private function createCrmIpAddressLists(Set $ipAddresses): array
    {
        $addressListRows = [];
        foreach ($ipAddresses as $ipAddress) {
            //for each ip address => create address list row
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
            } elseif (
                array_key_exists('name', $address)
                && Strings::startsWith($address['name'], self::COMMENT_SIGNATURE)
            ) {
                //some sections doesn't have comment attribute, ucrm uses name attribute instead
                $filtered[] = $address;
            }
        }

        return $filtered;
    }
}
