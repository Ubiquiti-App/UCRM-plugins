<?php

declare(strict_types=1);

namespace ViberNotifier\Facade;

use ViberNotifier\Data\NotificationData;
use ViberNotifier\Data\PluginData;
use ViberNotifier\Factory\MessageTextFactory;
use ViberNotifier\Service\Logger;
use ViberNotifier\Service\OptionsManager;
use ViberNotifier\Service\SmsNumberProvider;

class ViberNotifierFacade extends AbstractMessageNotifierFacade {

    private $pluginData;

    public function __construct( Logger $logger, MessageTextFactory $messageTextFactory, SmsNumberProvider $smsNumberProvider, OptionsManager $optionsManager )
    {
        parent::__construct($logger, $messageTextFactory, $smsNumberProvider);
        $this->pluginData = $optionsManager->load();
    }

    protected function sendMessage( NotificationData $notificationData, string $clientSmsNumber, string $messageBody ): void
    {
        $this->logger->debug("starting...");
 
        $url = 'https://chatapi.viber.com/pa/send_message';
        $data = array( 
            'min_api_version' => '1',
            'type' => 'text',
            'receiver' => $clientSmsNumber,
            'text' => $messageBody  
        );
        $options = array(
            'http' => array(
                'header'  =>  "Content-Type: application/json\r\n". "X-Viber-Auth-Token: " . $this->getBotToken() . "\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            )
        );
        $context  = stream_context_create($options);

        $this->logger->debug(sprintf('Sending: %s to %s', $messageBody, $clientSmsNumber));
        $result = file_get_contents($url, false, $context);       
        $this->logger->debug(sprintf('Result: %s', $result));
    }
    
    private function getBotToken(): string
    {
        return $this->pluginData->viberBotToken;
    }
}
