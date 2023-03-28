<?php

declare(strict_types=1);

namespace FioCz\Service;

use FioCz\Exception\CurlException;

class FioCurlExecutor extends CurlExecutor
{
    /**
     * @var OptionsManager
     */
    private $optionsManager;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(OptionsManager $optionsManager, Logger $logger)
    {
        $this->optionsManager = $optionsManager;
        $this->logger = $logger;
    }

    /**
     * @throws CurlException
     * @throws \ReflectionException
     */
    public function curlQuery($url, array $headers = [], array $parameters = [], bool $verifySsl = true): array
    {
        $options = $this->optionsManager->loadOptions();

        $lastProcessedTimestamp = (int) $options->lastProcessedTimestamp;
        try {
            $date = (new \DateTime())->setTimestamp($lastProcessedTimestamp)->format('Y-m-d H:i:s');
        } catch (\Exception $exception) {
            $date = $options->lastProcessedTimestamp;
        }
        if ($lastProcessedTimestamp + 30 > time()) {
            throw new CurlException('Execution skipped, because last request was less than 30 seconds ago: ' . $date);
        }
        $this->logger->debug(
            'Last processed at: ',
            [
                $lastProcessedTimestamp => $lastProcessedTimestamp ? $date : 'never',
            ]
        );

        $url_redacted = str_replace($options->token, '[*******]', $url);
        $this->logger->debug('Requesting from FIO API:', compact(
            'url_redacted',
            'headers',
            'parameters',
            'verifySsl'
        ));

        $startTime = microtime(true);
        $response = parent::curlQuery($url, $headers, $parameters, $verifySsl);
        $endTime = microtime(true);

        $this->logger->debug(sprintf('FIO API response success in %s sec', $endTime - $startTime));

        $options->lastProcessedTimestamp = time();
        $this->optionsManager->updateOptions();

        return $response;
    }
}
