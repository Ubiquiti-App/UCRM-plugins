<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */


declare(strict_types=1);


namespace SmsNotifier\Factory;


use SmsNotifier\Data\NotificationData;
use SmsNotifier\Data\PluginData;
use SmsNotifier\Service\Logger;
use SmsNotifier\Service\OptionsManager;

class MessageTextFactory
{
    private const DELIMITER = '%%';
    private const DATA_TYPES = [
        'client' => 'clientData',
        'invoice' => 'invoiceData',
        'payment' => 'paymentData',
        'service' => 'serviceData',
    ];
    private const DATETIME_FORMAT = 'Y-m-d H:i';

    /** @var Logger */
    private $logger;

    /** @var PluginData */
    private $pluginData;

    public function __construct(
        Logger $logger,
        OptionsManager $optionsManager
    )
    {
        $this->logger = $logger;
        $this->pluginData = $optionsManager->load();
    }

    public function createBody(NotificationData $notificationData): string
    {
        // configuration keys do not allow dots, replace with underscore
        $eventName = 'event_'.str_replace('.', '_', $notificationData->eventName);

        if (!property_exists($this->pluginData, $eventName)) {
            throw new \InvalidArgumentException('Unconfigured event name: '.$notificationData->eventName);
        }

        $eventText = trim((string)$this->pluginData->$eventName);
        $this->logger->debug($eventText);
        if (!$eventText) {
            return '';
        }

        $tokens = $this->createReplacementTokenArray($notificationData);
        $this->logger->debug($tokens);
        $eventText = str_replace(array_keys($tokens), array_values($tokens), $eventText);
        $this->logger->debug($eventText);
        return $eventText;
    }

    private function createReplacementTokenArray(NotificationData $notificationData): array
    {
        $tokens = [];
        foreach (self::DATA_TYPES as $typeName => $typeVariable) {
            if (empty($notificationData->$typeVariable)) {
                continue;
            }
            foreach ($notificationData->$typeVariable as $dataKey => $dataValue) {
                if (!is_array($dataValue)) {
                    if ($dataValue && strpos($dataKey, 'Date') > 0) {
                        $dateTimeValue = \DateTime::createFromFormat('Y-m-d\TH:i:sO', $dataValue);
                        if ($dateTimeValue) {
                            $dataValue = $this->formatDate($dateTimeValue);
                        }
                    }
                    $tokens[self::DELIMITER.$typeName.'.'.$dataKey.self::DELIMITER] = $dataValue ?? '';
                }
            }
        }

        return $tokens;
    }

    private function formatDate(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format(self::DATETIME_FORMAT);
    }


}
