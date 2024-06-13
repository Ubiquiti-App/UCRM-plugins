<?php
declare(strict_types=1);

namespace ViberNotifier\Service;


use ViberNotifier\Data\NotificationData;

class SmsNumberProvider
{

    /*
     * go through client's attributes and find Viber ID, if any
     */
    public function getUcrmClientNumber(NotificationData $notificationData): ?string
    {
        $attributes = $notificationData->clientData['attributes'] ?? [];
        foreach ($attributes as $attribute) 
        {
            if($attribute && $attribute['key'] == 'viberId')
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
