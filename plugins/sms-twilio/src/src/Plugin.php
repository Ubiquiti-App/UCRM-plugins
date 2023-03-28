<?php

declare(strict_types=1);


namespace SmsNotifier;

use Psr\Log\LogLevel;
use SmsNotifier\Facade\TwilioNotifierFacade;
use SmsNotifier\Factory\NotificationDataFactory;
use SmsNotifier\Service\Logger;
use SmsNotifier\Service\OptionsManager;
use SmsNotifier\Service\PluginDataValidator;

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
     * @var NotificationDataFactory
     */
    private $notificationDataFactory;

    public function __construct(
        Logger $logger,
        OptionsManager $optionsManager,
        PluginDataValidator $pluginDataValidator,
        TwilioNotifierFacade $notifierFacade,
        NotificationDataFactory $notificationDataFactory
    ) {
        $this->logger = $logger;
        $this->optionsManager = $optionsManager;
        $this->pluginDataValidator = $pluginDataValidator;
        $this->notifierFacade = $notifierFacade;
        $this->notificationDataFactory = $notificationDataFactory;
    }

    public function run(): void
    {
        if (PHP_SAPI === 'fpm-fcgi') {
            $this->logger->info('Twilio SMS over HTTP started');
            $this->processHttpRequest();
            $this->logger->info('HTTP request processing ended.');
        } elseif (PHP_SAPI === 'cli') {
            $this->logger->info('Twilio SMS over CLI started');
            $this->processCli();
            $this->logger->info('CLI process ended.');
        } else {
            throw new \UnexpectedValueException('Unknown PHP_SAPI type: ' . PHP_SAPI);
        }
    }

    private function processCli(): void
    {
        if ($this->pluginDataValidator->validate()) {
            $this->logger->info('Validating config');
            $this->optionsManager->load();
        }
    }

    private function processHttpRequest(): void
    {
        $pluginData = $this->optionsManager->load();
        if ($pluginData->logging_level) {
            $this->logger->setLogLevelThreshold(LogLevel::DEBUG);
        }

        $userInput = file_get_contents('php://input');
        if (! $userInput) {
            $this->logger->warning('no input');

            return;
        }

        $jsonData = @json_decode($userInput, true, 10);
        if (! isset($jsonData['uuid'])) {
            $this->logger->error('JSON error: ' . json_last_error_msg());

            return;
        }

        $notification = $this->notificationDataFactory->getObject($jsonData);
        if ($notification->changeType === 'test') {
            $this->logger->info('Webhook test successful.');

            return;
        }
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
    }
}
