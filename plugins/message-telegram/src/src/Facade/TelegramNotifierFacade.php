<?php

declare(strict_types=1);

namespace TelegramNotifier\Facade;

use TelegramNotifier\Data\NotificationData;
use TelegramNotifier\Data\PluginData;
use TelegramNotifier\Factory\MessageTextFactory;
use TelegramNotifier\Service\Logger;
use TelegramNotifier\Service\OptionsManager;
use TelegramNotifier\Service\SmsNumberProvider;

class TelegramNotifierFacade extends AbstractMessageNotifierFacade {

    private $pluginData;

    public function __construct( Logger $logger, MessageTextFactory $messageTextFactory, SmsNumberProvider $smsNumberProvider, OptionsManager $optionsManager )
    {
        parent::__construct($logger, $messageTextFactory, $smsNumberProvider);
        $this->pluginData = $optionsManager->load();
    }

    protected function sendMessage( NotificationData $notificationData, string $clientSmsNumber, string $messageBody ): void
    {
        $this->logger->debug("starting...");
        $bot_token = $this->getBotToken();
        $url = "https://api.telegram.org/" . $bot_token . "/sendMessage?chat_id=" . $clientSmsNumber . "&text=" . $messageBody;
        $this->logger->debug($url);
        $resp = file_get_contents($url);
        $this->logger->debug(sprintf('Sending: %s to %s', $messageBody, $clientSmsNumber));
    }
    
    private function getBotToken(): string
    {
        return $this->pluginData->telegramBotToken;
    }
}
