<?php

declare(strict_types=1);

namespace FioCz;

use FioCz\Facade\UcrmFacade;
use FioCz\FioCz\FioCz;
use FioCz\Service\Logger;
use FioCz\Service\OptionsManager;

class Importer
{
    /**
     * @var FioCz
     */
    private $fioCz;

    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UcrmFacade
     */
    private $ucrmFacade;

    /**
     * @var string
     */
    private $dateFormat = 'Y-m-d H:i:s';

    public function __construct(FioCz $fioCz, OptionsManager $optionsManager, Logger $logger, UcrmFacade $ucrmFacade)
    {
        $this->fioCz = $fioCz;
        $this->optionsManager = $optionsManager;
        $this->logger = $logger;
        $this->ucrmFacade = $ucrmFacade;
    }

    /**
     * @throws Exception\CurlException
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function import(): void
    {
        $optionsData = $this->optionsManager->loadOptions();

        $endDate = new \DateTimeImmutable('tomorrow');

        try {
            $startDate = new \DateTimeImmutable((string) $optionsData->startDate);
        } catch (\Exception $e) {
            $startDate = new \DateTimeImmutable('midnight first day of this month');
            $this->logger->notice(
                sprintf('Payments start date is not valid. Using "%s" instead.', $startDate->format($this->dateFormat))
            );
        }

        if ($endDate <= $startDate) {
            $this->logger->error(
                'Start date is equal or greater than end date',
                [
                    'startDate' => $startDate->format($this->dateFormat),
                    'endDate' => $endDate->format($this->dateFormat),
                ]
            );

            return;
        }

        $this->logger->debug('Processing range: ', [
            'startDate' => $startDate->format($this->dateFormat),
            'endDate' => $endDate->format($this->dateFormat),
        ]);

        if ($optionsData->lastProcessedPayment !== null && ! is_numeric($optionsData->lastProcessedPayment)) {
            $this->logger->error(
                'Last processed payment must be empty or a number',
                [
                    'lastProcessedPayment' => $optionsData->lastProcessedPayment,
                ]
            );

            return;
        }

        try {
            $methodId = $this->ucrmFacade->getPaymentMethod();

            $transactions = $this->fioCz->getTransactions(
                $optionsData->token,
                $startDate,
                $endDate,
                $optionsData->lastProcessedPayment === '' ? null : (int) $optionsData->lastProcessedPayment
            );
            $this->logger->info(sprintf('New incoming transactions found: %d', count($transactions)));
            $importedTransactions = 0;
            $skippedTransactions = 0;
            foreach ($transactions as $transaction) {
                if ($this->ucrmFacade->import($transaction, $methodId)) {
                    ++$importedTransactions;
                } else {
                    ++$skippedTransactions;
                }
            }
            unset($transactions);
            $this->logger->info(sprintf('Finished: imported %d transactions, %d skipped', $importedTransactions, $skippedTransactions));
        } catch (Exception\CurlException $exception) {
            switch ($exception->getCode()) {
                case 409:
                    $optionsData->lastProcessedTimestamp = time();
                    $this->optionsManager->updateOptions();
                    $this->logger->warning('HTTP Error 409 returned - usage limit exhausted, wait for 30s');
                    break;
                case 500:
                    $optionsData->lastProcessedTimestamp = time();
                    $this->optionsManager->updateOptions();
                    $this->logger->error('HTTP Error 500 returned - is token valid and not expired?');
                    break;
                default:
                    throw $exception;
            }
        }
    }
}
