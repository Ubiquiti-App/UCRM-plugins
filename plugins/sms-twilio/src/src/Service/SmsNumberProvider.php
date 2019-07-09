<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */


declare(strict_types=1);


namespace SmsNotifier\Service;


use SmsNotifier\Data\NotificationData;
use SmsNotifier\Data\PluginData;

class SmsNumberProvider
{
    /** @var PluginData */
    private $pluginData;

    /**
     * SmsNumberProvider constructor.
     * @param OptionsManager $optionsManager
     * @throws
     * \ReflectionException
     */
    public function __construct(OptionsManager $optionsManager)
    {
        $this->pluginData = $optionsManager->load();
    }


    /*
     * go through client's contacts and find an applicable one, if any
     */
    public function getUcrmClientNumber(NotificationData $notificationData): ?string
    {
        $contacts = $this->filterContactsByContactType($notificationData->clientData['contacts']);
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

    /**
     * If there no contact types defined return all contacts, otherwise return only contacts corresponding by type
     *
     * @param array $contacts
     * @return array
     */
    private function filterContactsByContactType(array $contacts): array
    {
        if (empty($this->pluginData->contactTypeFilter)) {
            return $contacts;
        }

        // parse and sanitize setting values
        $settingsContactTypes = explode(',', $this->pluginData->contactTypeFilter);
        foreach ($settingsContactTypes as &$contactType) {
            $contactType = trim($contactType);
            $contactType = mb_strtolower($contactType, 'UTF-8');
        }
        unset($contactType);

        // compare and filter contact type with settings
        $filteredContacts = [];
        foreach ($contacts as $contact) {
            foreach ($contact['types'] as $type) {
                $clientContactType = trim($type['name']);
                $clientContactType = mb_strtolower($clientContactType, 'UTF-8');
                if (in_array($clientContactType, $settingsContactTypes)) {
                    $filteredContacts[] = $contact;
                    break;
                }
            }
        }
        return $filteredContacts;
    }
}
