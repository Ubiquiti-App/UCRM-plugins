<?php
declare(strict_types=1);

namespace TelegramNotifier\Service;


use TelegramNotifier\Data\NotificationData;

class SmsNumberProvider
{

    /*
     * go through client's attributes and find Telegram ID, if any
     */
    public function getUcrmClientNumber(NotificationData $notificationData): ?string
    {
        $attributes = $notificationData->clientData['attributes'] ?? [];
        foreach ($attributes as $attribute) 
        {
            if($attribute && $attribute['key'] == 'telegramId')
            {
                if(empty($attribute['value']))
                {
                    return null;
                }
                return $attribute['value'];
            }
        }
        return null;
    }
}
