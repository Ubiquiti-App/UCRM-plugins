<?php

declare(strict_types=1);


namespace App\Service;

use App\HttpGetParametersData;
use App\StringHelper;
use Ds\Set;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;

final class ServicePlansDataProvider
{
    public function __construct(
        private UcrmApi $api,
        private HttpGetParametersData $httpParametersData
    ) {
    }

    public function getServicePlans(): array
    {
        $parameters = [
            'organizationId' => $this->httpParametersData->organization,
            'createdDateFrom' => $this->httpParametersData->since,
            'createdDateTo' => $this->httpParametersData->until,
            // 1 = Unpaid, 2 = Partially paid, 3 = Paid
            'status' => [1, 2, 3],
        ];
        $parameters = array_filter($parameters);

        if (($parameters['createdDateFrom'] ?? null) !== null) {
            $parameters['createdDateFrom'] = (new \DateTimeImmutable($parameters['createdDateFrom']))->format('Y-m-d');
        } else {
            $firstInvoice = $this->api->get(
                'invoices',
                [
                    'organizationId' => $parameters['organizationId'],
                    'limit' => 1,
                    'order' => 'createdDate',
                    'direction' => 'ASC',
                ]
            );
            if (count($firstInvoice) === 0) {
                return [];
            }

            $parameters['createdDateFrom'] = (new \DateTimeImmutable($firstInvoice[0]['createdDate']))->format('Y-m-d');
        }

        if (($parameters['createdDateTo'] ?? null) !== null) {
            $parameters['createdDateTo'] = new \DateTimeImmutable($parameters['createdDateTo']);
            $parameters['createdDateTo'] = $parameters['createdDateTo']->format('Y-m-d');
        } else {
            $parameters['createdDateTo'] = null;
        }

        $allInvoices = new Set();
        $startDate = new \DateTimeImmutable($parameters['createdDateFrom']);
        $endDate = new \DateTimeImmutable($parameters['createdDateTo'] ?? 'now');
        $interval = new \DateInterval('P10D');

        $currentStartDate = $startDate;

        while ($currentStartDate <= $endDate) {
            $currentEndDate = min($currentStartDate->add($interval)->sub(new \DateInterval('P1D')), $endDate);

            $periodParameters = $parameters;
            $periodParameters['createdDateFrom'] = $currentStartDate->format('Y-m-d');
            $periodParameters['createdDateTo'] = $currentEndDate->format('Y-m-d');

            $periodInvoices = $this->api->get('invoices', $periodParameters);

            foreach ($periodInvoices as $invoice) {
                $allInvoices->add($invoice);
            }

            $currentStartDate = $currentEndDate->add(new \DateInterval('P1D'));
        }

        $services = $this->api->get('clients/services', [
            'organizationId' => $_GET['organization'],
        ]);

        $servicePlansMap = [];
        foreach ($services as $service) {
            if (! array_key_exists($service['servicePlanId'], $servicePlansMap)) {
                $servicePlansMap[$service['servicePlanId']] = [
                    'name' => $service['servicePlanName'],
                    'totalIssued' => 0,
                    'totalPaid' => 0,
                    'servicesIds' => [$service['id']],
                ];
            } else {
                $servicePlansMap[$service['servicePlanId']]['servicesIds'][] = $service['id'];
            }
        }

        foreach ($allInvoices as $invoice) {
            foreach ($invoice['items'] as $invoiceItem) {
                $price = $invoiceItem['total'] + $invoiceItem['discountTotal'];
                if ($invoiceItem['type'] !== 'service' || $price <= 0) {
                    continue;
                }

                foreach ($servicePlansMap as $servicePlanId => $servicePlan) {
                    if (! in_array($invoiceItem['serviceId'], $servicePlan['servicesIds'], true)) {
                        continue;
                    }

                    $servicePlansMap[$servicePlanId]['totalIssued'] += $price;
                    if ($invoice['status'] === 3) {
                        $servicePlansMap[$servicePlanId]['totalPaid'] += $price;
                    }
                }
            }
        }

        return $servicePlansMap;
    }
}
