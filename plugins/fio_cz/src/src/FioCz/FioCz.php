<?php

declare(strict_types=1);

namespace FioCz\FioCz;

use FioCz\Service\FioCurlExecutor;

class FioCz
{
    /**
     * @var FioCurlExecutor
     */
    private $fioCurlExecutor;

    public function __construct(FioCurlExecutor $fioCurlExecutor)
    {
        $this->fioCurlExecutor = $fioCurlExecutor;
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
        $transactions = $this->transformTransactionsData($transactions);
        $transactions = $this->removeIncomingTransactions($transactions);

        if ($lastProcessedPayment) {
            $transactions = $this->removePreviouslyProcessedTransactions($transactions, $lastProcessedPayment);
        }

        return $transactions;
    }

    /**
     * @throws \FioCz\Exception\CurlException
     */
    private function downloadTransactionsFromFio(string $token, \DateTimeImmutable $since, \DateTimeImmutable $until)
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
                    'reference' => $transaction['column5']['value'],
                    'id' => $transaction['column22']['value'],
                    'data' => $data,
                ];
            },
            $data['accountStatement']['transactionList']['transaction']
        );
    }

    private function removeIncomingTransactions(array $transactions): array
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
        while ($transactions && $transactions[0]['id'] !== $lastProcessedPayment) {
            array_shift($transactions);
        }

        if (! $transactions) {
            throw new \Exception(sprintf('Could not find previously processed transaction %d.', $lastProcessedPayment));
        }

        array_shift($transactions);

        return $transactions;
    }
}
