<?php

declare(strict_types=1);

namespace TelegramNotifier\Facade;

use TelegramNotifier\Data\NotificationData;
use TelegramNotifier\Factory\MessageTextFactory;
use TelegramNotifier\Service\Logger;
use TelegramNotifier\Service\SmsNumberProvider;

/*
 * send message to client's number
 */
abstract class AbstractMessageNotifierFacade
{
    protected $logger;
    protected $messageTextFactory;
    protected $smsNumberProvider;

    public function __construct(Logger $logger, MessageTextFactory $messageTextFactory, SmsNumberProvider $smsNumberProvider ) 
    {
        $this->logger = $logger;
        $this->messageTextFactory = $messageTextFactory;
        $this->smsNumberProvider = $smsNumberProvider;
    }

    /**
     * @param NotificationData $notificationData
     * @param string $clientSmsNumber
     * @param string $messageBody
     */
    abstract protected function sendMessage( NotificationData $notificationData, string $clientSmsNumber, string $messageBody ): void;


    /*
     * sets up the body and uses the implementation's sendMessage() to send
     */
    public function notify(NotificationData $notificationData): void
    {
        $clientSmsNumber = $this->smsNumberProvider->getUcrmClientNumber($notificationData);
        if (empty($clientSmsNumber)) 
        {
            $this->logger->warning('No Telegram Id found for client: ' . $notificationData->clientId);
            return;
        }
        $messageBody = $this->messageTextFactory->createBody($notificationData);
        if (!$messageBody) 
        {
            $this->logger->info('No text configured for event: ' . $notificationData->eventName);
            return;
        }
        $this->sendMessage($notificationData, $clientSmsNumber, $messageBody);
    }

}
