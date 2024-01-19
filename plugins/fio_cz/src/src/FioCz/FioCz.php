<?php

declare(strict_types=1);

namespace FioCz\FioCz;

use FioCz\Service\FioCurlExecutor;
use FioCz\Service\Logger;

class FioCz
{
    /**
     * @var FioCurlExecutor
     */
    private $fioCurlExecutor;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(FioCurlExecutor $fioCurlExecutor, Logger $logger)
    {
        $this->fioCurlExecutor = $fioCurlExecutor;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     * @throws \FioCz\Exception\CurlException
     */
    public function getTransactions(
        $token,
        \DateTimeImmutable $since,
        \DateTimeImmutable $until,
        ?int $lastProcessedPayment = null
    ): array {
        $transactions = $this->downloadTransactionsFromFio($token, $since, $until);
        $this->logger->debug(sprintf('Transactions found: %d', count($transactions)));
        $transactions = $this->transformTransactionsData($transactions);
        $transactions = $this->filterIncomingTransactions($transactions);
        $this->logger->debug(sprintf('Incoming transactions: %d', count($transactions)));
        if ($lastProcessedPayment) {
            $transactions = $this->removePreviouslyProcessedTransactions($transactions, $lastProcessedPayment);
            $this->logger->debug(sprintf('Previously unseen incoming transactions: %d', count($transactions)));
        } else {
            $this->logger->debug('No previously processed payments, using all.');
        }

        return $transactions;
    }

    /**
     * @throws \FioCz\Exception\CurlException
     * @throws \ReflectionException
     */
    private function downloadTransactionsFromFio(string $token, \DateTimeImmutable $since, \DateTimeImmutable $until): array
    {
        $url = sprintf(
            'https://www.fio.cz/ib_api/rest/periods/%s/%s/%s/transactions.json',
            $token,
            $since->format('Y-m-d'),
            $until->format('Y-m-d')
        );

        return $this->fioCurlExecutor->curlQuery(
            $url,
            [
                'Content-Type: application/json',
            ]
        );
    }

    private function transformTransactionsData(array $data): array
    {
        return array_map(
            function ($transaction) {
                $data = [];

                foreach ($transaction as $column) {
                    if (! $column) {
                        continue;
                    }

                    $data[$column['name']] = $column['value'];
                }

                return [
                    'amount' => $transaction['column1']['value'],
                    'currency' => $transaction['column14']['value'],
                    'date' => $transaction['column0']['value'],
                    'reference' => $transaction['column5']['value'] ?? '',
                    'id' => $transaction['column22']['value'],
                    'data' => $data,
                ];
            },
            $data['accountStatement']['transactionList']['transaction']
        );
    }

    private function filterIncomingTransactions(array $transactions): array
    {
        return array_filter(
            $transactions,
            function ($transaction) {
                return $transaction['amount'] > 0;
            }
        );
    }

    /**
     * @throws \Exception
     */
    private function removePreviouslyProcessedTransactions(array $transactions, int $lastProcessedPayment): array
    {
        $unprocessedTransactions = [];
        $unprocessed = false;

        foreach ($transactions as $key => $transaction) {
            // $lastProcessedPayment was already seen, copy the rest of the array
            // else loop through transactions until $lastProcessedPayment is found
            // this assumes that the order of already seen transactions is immutable
            // (or at least that new transactions are inserted after those returned in previous invocations)
            if ($unprocessed) {
                $this->logger->debug('Unprocessed transaction ID:' . $transaction['id']);
                $unprocessedTransactions[] = $transaction;
            } elseif ($transaction['id'] === $lastProcessedPayment) {
                $unprocessed = true;
                $this->logger->debug('Last already processed payment found: ' . $transaction['id']);
            } else {
                $this->logger->debug('Already processed transaction ID:' . $transaction['id']);
            }
        }

        if (! $unprocessed) {
            throw new \Exception(sprintf('Could not find previously processed transaction %d.', $lastProcessedPayment));
        }

        return $unprocessedTransactions;
    }
}
