<?php

declare(strict_types=1);


namespace SmsNotifier;


use SmsNotifier\Facade\TwilioNotifierFacade;
use SmsNotifier\Factory\NotificationDataFactory;
use SmsNotifier\Service\OptionsManager;
use SmsNotifier\Service\PluginDataValidator;
use SmsNotifier\Service\Logger;

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
        } elseif (PHP_SAPI === 'cli') {
            $this->logger->info('Twilio SMS over CLI started');
            $this->processCli();
        } else {
            throw new \UnexpectedValueException('Unknown PHP_SAPI type: ' . PHP_SAPI);
        }
    }

    private function processCli(): void
    {
        if ($this->pluginDataValidator->validate()) {
            $this->logger->info('Validating config');
            $this->optionsManager->load();
            $this->logger->info('CLI process ended.');
        }
    }

    private function processHttpRequest(): void
    {
        $userInput = file_get_contents('php://input');
        if ($userInput) {
            $jsonData = @json_decode($userInput, true, 10);
            if (isset($jsonData['uuid'])) {
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
            } else {
                $this->logger->error('JSON error: ' . json_last_error_msg());
            }
        } else {
            $this->logger->debug('no input');
        }
    }
}
