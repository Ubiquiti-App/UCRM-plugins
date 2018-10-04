<?php

declare(strict_types=1);

namespace SmsNotifier\Facade;

use SmsNotifier\Data\NotificationData;
use SmsNotifier\Factory\MessageTextFactory;
use SmsNotifier\Service\Logger;
use SmsNotifier\Service\SmsNumberProvider;
use Twilio\Exceptions\HttpException;

/*
 * send message to client's number
 */
abstract class AbstractMessageNotifierFacade
{
    /**
     * @var Logger
     */
    protected $logger;

    /** @var MessageTextFactory */
    protected $messageTextFactory;

    /** @var SmsNumberProvider */
    protected $smsNumberProvider;

    public function __construct(
        Logger $logger,
        MessageTextFactory $messageTextFactory,
        SmsNumberProvider $smsNumberProvider
    ) {
        $this->logger = $logger;
        $this->messageTextFactory = $messageTextFactory;
        $this->smsNumberProvider = $smsNumberProvider;
    }

    /**
     * implement in subclass with the specific messaging provider
     * @see TwilioNotifierFacade::sendMessage()
     *
     * @param NotificationData $notificationData
     * @param string $clientSmsNumber
     * @param string $messageBody
     */
    abstract protected function sendMessage(
        NotificationData $notificationData,
        string $clientSmsNumber,
        string $messageBody
    ): void;


    /*
     * sets up the body and uses the implementation's sendMessage() to send
     */
    public function notify(NotificationData $notificationData): void
    {
        $clientSmsNumber = $this->smsNumberProvider->getUcrmClientNumber($notificationData);
        if (empty($clientSmsNumber)) {
            $this->logger->warning('No SMS number found for client: ' . $notificationData->clientId);
            return;
        }
        $messageBody = $this->messageTextFactory->createBody($notificationData);
        if (!$messageBody) {
            $this->logger->info('No text configured for event: ' . $notificationData->eventName);
            return;
        }

        try {
            $this->sendMessage($notificationData, $clientSmsNumber, $messageBody);
        } catch (HttpException $httpException) {
            $this->logger->error($httpException->getCode().' '.$httpException->getMessage());
        }
    }

}
