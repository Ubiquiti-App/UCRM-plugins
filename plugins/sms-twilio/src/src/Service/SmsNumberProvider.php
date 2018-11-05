<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */


declare(strict_types=1);


namespace SmsNotifier\Service;


use SmsNotifier\Data\NotificationData;

class SmsNumberProvider
{

    /*
     * go through client's contacts and find an applicable one, if any
     */
    public function getUcrmClientNumber(NotificationData $notificationData): ?string
    {
        $contacts = $notificationData->clientData['contacts'] ?? [];
        foreach ($contacts as $contact) {
            if ($this->isContactApplicable($notificationData->entity, $contact)) {
                return $contact['phone'];
            }
        }
        return null;
    }

    /*
     * not every contact has a phone; also check if the type of notification is applicable to contact
     */
    protected function isContactApplicable(string $entity, array $contact = null): bool
    {
        if (!$contact || empty($contact['phone'])) {
            return false;
        }
        switch ($entity) {
            case 'invoice':
            case 'payment':
                return $contact['isBilling'] ?? false;
            case 'client':
            case 'service':
            default:
                return $contact['isContact'] ?? false;
        }
    }

}
