<?php

declare(strict_types=1);

namespace App\Service;

use League\Csv\Writer;
use SplTempFileObject;

class CsvGenerator
{
    /**
     * @var string[]
     */
    private $stateMap;

    /**
     * @var string[]
     */
    private $countryMap;

    public function __construct(array $countries, array $states)
    {
        $this->countryMap = $this->mapCountries($countries);
        $this->stateMap = $this->mapStates($states);
    }

    public function generate(string $filename, array $invoices): void
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject());

        $csv->insertOne($this->getHeaderLine());

        foreach ($invoices as $invoice) {
            $csv->insertOne($this->getInvoiceLine($invoice));
        }

        $csv->download($filename);
    }

    private function getHeaderLine(): array
    {
        return [
            'Number',
            'Status',
            'Created date',
            'Due date',
            'Currency',
            'Total',
            'Taxes',
            'Discount',
            'Amount paid',
            'Amount due',
            'Client firstname',
            'Client lastname',
            'Client company name',
            'Client address',
        ];
    }

    private function getInvoiceLine(array $invoice): array
    {
        return [
            $invoice['number'],
            $this->formatInvoiceStatus($invoice['status']),
            $invoice['createdDate'],
            $invoice['dueDate'],
            $invoice['currencyCode'],
            $invoice['total'],
            $this->sumTaxes($invoice['taxes']),
            $invoice['discount'] ?? 0,
            $invoice['amountPaid'],
            $invoice['total'] - $invoice['amountPaid'],
            $invoice['clientFirstName'],
            $invoice['clientLastName'],
            $invoice['clientCompanyName'],
            $this->formatClientAddress($invoice),
        ];
    }

    private function formatInvoiceStatus(int $status): string
    {
        switch ($status) {
            case 0:
                return 'Draft';
            case 1:
                return 'Unpaid';
            case 2:
                return 'Partially paid';
            case 3:
                return 'Paid';
            case 4:
                return 'Void';
            default:
                return '<Unknown>';
        }
    }

    private function sumTaxes(array $taxes): float
    {
        $sum = 0.0;

        foreach ($taxes as $tax) {
            $sum += $tax['totalValue'];
        }

        return $sum;
    }

    private function formatClientAddress(array $invoice)
    {
        return sprintf(
            '%s, %s, %s, %s',
            $invoice['clientStreet1'] . ($invoice['clientStreet2'] ? ', ' . $invoice['clientStreet2'] : ''),
            $invoice['clientCity'],
            $invoice['clientZipCode'],
            $this->formatCountry($invoice['clientCountryId'], $invoice['clientStateId'])
        );
    }

    private function formatCountry(?int $countryId, ?int $stateId): string
    {
        if ($countryId === null) {
            return '';
        }

        if ($stateId !== null) {
            return ($this->stateMap[$stateId] ?? '') . ', ' . ($this->countryMap[$countryId] ?? '');
        }

        return $this->countryMap[$countryId] ?? '';
    }

    private function mapCountries(array $countries): array
    {
        $map = [];

        foreach ($countries as $country) {
            $map[$country['id']] = $country['name'];
        }

        return $map;
    }

    private function mapStates(array $states): array
    {
        $map = [];

        foreach ($states as $state) {
            $map[$state['id']] = $state['name'];
        }

        return $map;
    }
}
