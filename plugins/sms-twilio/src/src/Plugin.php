<?php

declare(strict_types=1);


namespace SmsNotifier;


use SmsNotifier\Facade\TwilioNotifierFacade;
use SmsNotifier\Factory\NotificationDataFactory;
use SmsNotifier\Service\PluginDataValidator;
use SmsNotifier\Service\Logger;
use SmsNotifier\Service\OptionsManager;
use SmsNotifier\Service\UcrmApi;

class Plugin
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
     * @var PluginDataValidator
     */
    private $pluginDataValidator;

    /**
     * @var TwilioNotifierFacade
     */
    private $notifierFacade;

    /**
     * @var UcrmApi
     */
    private $ucrmApi;

    /**
     * @var NotificationDataFactory
     */
    private $notificationDataFactory;

    public function __construct(
        Logger $logger,
        OptionsManager $optionsManager,
        PluginDataValidator $pluginDataValidator,
        TwilioNotifierFacade $notifierFacade,
        NotificationDataFactory $notificationDataFactory,
        UcrmApi $ucrmApi
    ) {
        $this->logger = $logger;
        $this->optionsManager = $optionsManager;
        $this->pluginDataValidator = $pluginDataValidator;
        $this->notifierFacade = $notifierFacade;
        $this->notificationDataFactory = $notificationDataFactory;
        $this->ucrmApi = $ucrmApi;
    }

    public function run(): void
    {
        if (PHP_SAPI === 'fpm-fcgi') {
            $this->processHttpRequest();
        } elseif (PHP_SAPI === 'cli') {
            $this->processCli();
        } else {
            throw new \UnexpectedValueException('Unknown PHP_SAPI type: ' . PHP_SAPI);
        }
    }

    private function processCli(): void
    {
        if ($this->pluginDataValidator->validate()) {
            $this->logger->info('CLI process started, validating config');
            $this->optionsManager->load();
            $this->logger->info('CLI process ended.');
        }
    }

    private function processHttpRequest(): void
    {
        $userInput = file_get_contents('php://input');
        if ($userInput) {
            // this data came from an outside request, anyone knowing the URL could have sent it.
            $jsonDataUnverified = @json_decode($userInput, true, 10);
            if (!empty($jsonDataUnverified['uuid'])) {
                // verify by requesting the UUID of the given webhook
                $jsonDataVerified = $this->ucrmApi->query('webhook-events/'.$jsonDataUnverified['uuid']);
                $this->logger->debug($jsonDataVerified);
                if (isset($jsonDataVerified['uuid'])) {
                    $notification = $this->notificationDataFactory->getObject($jsonDataVerified);
                    if (! $notification->clientId) {
                        $this->logger->warning('No client specified, cannot notify them.');
                        return;
                    }
                    try {
                        $this->notifierFacade->notify($notification);
                    } catch (\Exception $ex) {
                        $this->logger->error($ex->getMessage());
                        $this->logger->warning($ex->getTraceAsString());
                    }
                } else {
                    $this->logger->warning('Invalid UUID: ' . $jsonDataUnverified['uuid']);
                }
            } else {
                $this->logger->error('JSON error: ' . json_last_error_msg());
            }
        } else {
            $this->logger->debug('no input');
        }
    }
}
