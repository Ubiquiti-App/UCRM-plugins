<?php

declare(strict_types=1);

namespace FioCz\Facade;

use FioCz\Service\Logger;
use FioCz\Service\OptionsManager;
use FioCz\Service\UcrmApi;

class UcrmFacade
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    public function __construct(Logger $logger, OptionsManager $optionsManager, UcrmApi $ucrmApi)
    {
        $this->logger = $logger;
        $this->optionsManager = $optionsManager;
        $this->ucrmApi = $ucrmApi;
    }

    /**
     * @throws \FioCz\Exception\CurlException
     * @throws \ReflectionException
     */
    public function import(
        array $transaction,
        string $methodId
    ): bool
    {
        $optionsData = $this->optionsManager->loadOptions();

        $this->logger->info(sprintf('Processing transaction %s.', $transaction['id']));

        $matched = $this->matchClientFromUcrm($transaction, $optionsData->paymentMatchAttribute);
        if ($matched || $optionsData->importUnattached) {
            if ($matched) {
                [$clientId, $invoiceId] = $matched;
                $this->logger->info(sprintf('Matched to client %s, invoice %s', $clientId, $invoiceId));
            } else {
                $this->logger->info('Not matched, importing as unattached');
            }
            $this->sendPaymentToUcrm(
                $this->transformTransactionToUcrmPayment(
                    $transaction,
                    $methodId,
                    $clientId ?? null,
                    $invoiceId ?? null
                )
            );

            $optionsData->lastProcessedPayment = $transaction['id'];
            $this->optionsManager->updateOptions();
            $this->logger->debug(sprintf('lastProcessedPayment set to %s', $optionsData->lastProcessedPayment));
            return true;
        } else {
            $this->logger->info('Not matched, skipping');
            return false;
        }
    }

    /**
     * @throws \FioCz\Exception\CurlException
     * @throws \ReflectionException
     */
    private function matchClientFromUcrm(array $transaction, $matchBy): ?array
    {
        $endpoint = 'clients';

        if ($matchBy === 'invoiceNumber') {
            $endpoint = 'invoices';
            $parameters = [
                'number' => $transaction['reference'],
            ];
        } elseif ($matchBy === 'clientId') {
            $parameters = [
                'id' => $transaction['reference'],
            ];
        } elseif ($matchBy === 'clientUserIdent') {
            $parameters = [
                'userIdent' => $transaction['reference'],
            ];
        } else {
            $parameters = [
                'customAttributeKey' => $matchBy,
                'customAttributeValue' => $transaction['reference'],
            ];
        }

        $results = $this->ucrmApi->query($endpoint, $parameters);

        switch (\count($results)) {
            case 0:
                $this->logger->warning(sprintf('No result found for transaction %s.', $transaction['id']));

                return null;
            case 1:
                if ($matchBy === 'invoiceNumber') {
                    return [$results[0]['clientId'], $results[0]['id']];
                }

                return [$results[0]['id'], null];
            default:
                $this->logger->warning(
                    sprintf('Multiple matching results found for transaction %s.', $transaction['id'])
                );

                return null;
        }
    }

    private function transformTransactionToUcrmPayment(
        array $transaction,
        string $methodId,
        ?int $clientId = null,
        ?int $invoiceId = null
    ): array {
        try {
            $date = new \DateTimeImmutable($transaction['date']);
        } catch (\Exception $e) {
            $this->logger->warning('Cannot create date from value, using "now"', [
                'dateValue' => $transaction['date']
            ]);
            $date = new \DateTimeImmutable();
        }
        $note = '';
        foreach ($transaction['data'] as $key => $value) {
            $note .= $key . ': ' . $value . PHP_EOL;
        }

        $newPaymentData = [
            'clientId' => $clientId,
            'amount' => $transaction['amount'],
            'currencyCode' => $transaction['currency'],
            'note' => $note,
            'invoiceIds' => $invoiceId ? [$invoiceId] : [],
            'providerName' => 'Fio CZ',
            'providerPaymentId' => (string) $transaction['id'],
            'providerPaymentTime' => $date->format('Y-m-d\TH:i:sO'),
            'applyToInvoicesAutomatically' => ! $invoiceId,
        ];
        if ($this->getVersion() > 2) {
            $newPaymentData['methodId'] = $methodId;
        } else {
            $newPaymentData['method'] = (int) $methodId;
        }
        return $newPaymentData;
    }

    /**
     * @throws \FioCz\Exception\CurlException
     * @throws \ReflectionException
     */
    private function sendPaymentToUcrm(array $payment): void
    {
        $this->logger->debug('POST /api/v1.0/payments', $payment);
        $this->ucrmApi->command(
            'payments',
            'POST',
            $payment
        );

        $this->logger->info('Payment created');
    }

    public function getPaymentMethod(): string
    {
        if ($this->getVersion() > 2) {
            return "4145b5f5-3bbc-45e3-8fc5-9cda970c62fb"; // no need to query methods, as this one is built-in
        } else {
            return "3"; // hard-coded backwards compat for 'bank transfer'
        }
    }

    private function getVersion(): int {
        return ($this->optionsManager->loadOptions()->unmsLocalUrl ?? null)
            ? 3
            : 2;
    }
}
