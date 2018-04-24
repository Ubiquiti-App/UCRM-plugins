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
        }

        if ($endDate <= $startDate) {
            $this->logger->notice(
                'Start date is equal or greater than end date',
                [
                    'startDate' => $startDate->format('Y-m-d H:i:s'),
                    'endDate' => $endDate->format('Y-m-d H:i:s'),
                ]);
            return;
        }

        $transactions = $this->fioCz->getTransactions(
            $optionsData->token,
            $startDate,
            $endDate,
            $optionsData->lastProcessedPayment === '' ? null : $optionsData->lastProcessedPayment
        );

        foreach ($transactions as $transaction) {
            $this->ucrmFacade->import($transaction);
        }
    }
}
