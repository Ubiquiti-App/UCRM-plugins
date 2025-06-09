<?php

declare(strict_types=1);

namespace FioCz\Facade;

use FioCz\Exception\CurlException;
use FioCz\Service\Logger;
use FioCz\Service\OptionsManager;
use FioCz\Service\UcrmApi;

class UcrmFacade
{
    public function __construct(
        private Logger $logger,
        private OptionsManager $optionsManager,
        private UcrmApi $ucrmApi
    ) {
    }

    /**
     * @throws \FioCz\Exception\CurlException
     * @throws \ReflectionException
     */
    public function import(
        array $transaction,
        string $methodId
    ): bool {
        $optionsData = $this->optionsManager->loadOptions();

        $this->logger->info(sprintf('Processing transaction %s.', $transaction['id']));

        $matchAttributes = explode(';', $optionsData->paymentMatchAttribute);

        $matched = null;
        foreach ($matchAttributes as $matchAttribute) {
            $matched = $this->matchClientFromUcrm($transaction, $matchAttribute);
            if ($matched !== null) {
                break;
            }
        }

        if ($matched === null && ! $optionsData->importUnattached) {
            $this->logger->warning(sprintf('Not matched, skipping transaction %s', $transaction['id']));
            $this->logger->info('To import unmatched payments, enable "Import all payments" in plugin\'s settings.)');

            return false;
        }

        if ($matched !== null) {
            [$clientId, $invoiceId] = $matched;
            if ($invoiceId === null) {
                $this->logger->info(sprintf('Matched transaction %s to client %s', $transaction['id'], $clientId));
            } else {
                $this->logger->info(sprintf('Matched transaction %s to client %s, invoice %s', $transaction['id'], $clientId, $invoiceId));
            }

            $this->sendMatched($transaction, $methodId, $clientId, $invoiceId);
        } else {
            $this->logger->info(sprintf('Not matched transaction %s, importing as unattached', $transaction['id']));
            $this->sendUnmatched($transaction, $methodId);
        }

        $optionsData->lastProcessedPayment = $transaction['id'];
        $optionsData->lastProcessedPaymentDateTime = $transaction['date'];
        $this->optionsManager->updateOptions();
        $this->logger->debug(sprintf('lastProcessedPayment set to %s', $optionsData->lastProcessedPayment));

        return true;
    }

    public function getPaymentMethod(): string
    {
        if ($this->getVersion() > 2) {
            return '4145b5f5-3bbc-45e3-8fc5-9cda970c62fb'; // no need to query methods, as this UUID is built-in
        }

        return '3'; // hard-coded backwards compatibility for 'bank transfer'
    }

    /**
     * @return array<int, ?int>|null
     * @throws \FioCz\Exception\CurlException
     * @throws \ReflectionException
     */
    private function matchClientFromUcrm(array $transaction, string $matchBy): ?array
    {
        $matchBy = trim($matchBy);
        if ($matchBy === '') {
            return null;
        }

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
                'dateValue' => $transaction['date'],
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

    private function getVersion(): int
    {
        return ($this->optionsManager->loadOptions()->unmsLocalUrl ?? null)
            ? 3
            : 2;
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    private function sendMatched(array $transaction, string $methodId, int $clientId, ?int $invoiceId = null): void
    {
        try {
            $this->sendPaymentToUcrm(
                $this->transformTransactionToUcrmPayment($transaction, $methodId, $clientId, $invoiceId)
            );
        } catch (CurlException $exception) {
            if ($exception->getCode() !== 422) {
                throw $exception;
            }

            $this->logger->info(
                sprintf(
                    'Invoice ID %s is either already paid, voided or a draft. Importing without invoice ID.',
                    $invoiceId
                )
            );

            $this->sendPaymentToUcrm($this->transformTransactionToUcrmPayment($transaction, $methodId, $clientId));
        }
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    private function sendUnmatched(array $transaction, string $methodId): void
    {
        $this->sendPaymentToUcrm(
            $this->transformTransactionToUcrmPayment($transaction, $methodId)
        );
    }
}
