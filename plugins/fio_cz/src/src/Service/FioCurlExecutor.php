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

    public function __construct(OptionsManager $optionsManager, Logger $logger)
    {
        $this->optionsManager = $optionsManager;
    }

    /**
     * @throws CurlException
     */
    public function curlQuery($url, array $headers = [], array $parameters = [])
    {
        $options = $this->optionsManager->loadOptions();

        $lastProcessedTimestamp = (int) $options->lastProcessedTimestamp;
        if ($lastProcessedTimestamp + 30 > time()) {
            throw new CurlException('Execution skipped, because last request was less than 30 seconds ago.');
        }

        $response = parent::curlQuery($url, $headers, $parameters);

        $options->lastProcessedTimestamp = time();
        $this->optionsManager->updateOptions();

        return $response;
    }
}
