<?php

declare(strict_types=1);

namespace TelegramNotifier\Factory;

use TelegramNotifier\Data\NotificationData;
use TelegramNotifier\Service\Logger;
use TelegramNotifier\Service\UcrmApi;

class NotificationDataFactory
{
    private $ucrmApi;

    public function __construct( UcrmApi $ucrmApi )
    {
        $this->ucrmApi = $ucrmApi;
    }

    public function getObject($jsonData): NotificationData {
        $notificationData = new NotificationData();
        $notificationData->uuid = $jsonData['uuid'];
        $notificationData->changeType = $jsonData['changeType'];
        $notificationData->entity = $jsonData['entity'];
        $notificationData->entityId = $jsonData['entityId'] ? (int) $jsonData['entityId'] : null;
        $notificationData->eventName = $jsonData['eventName'];
        $this->resolveUcrmData($notificationData);

        return $notificationData;
    }

    private function resolveUcrmData(NotificationData $notificationData): void
    {
        switch($notificationData->entity) {
            case 'client':
                $notificationData->clientId = $notificationData->entityId;
                break;
            case 'invoice':
                $notificationData->clientId = $this->getInvoiceData($notificationData)['clientId'] ?? null;
                break;
            case 'payment':
                $notificationData->clientId = $this->getPaymentData($notificationData)['clientId'] ?? null;
                break;
            case 'service':
                $notificationData->clientId = $this->getServiceData($notificationData)['clientId'] ?? null;
                break;
        }
        if ($notificationData->clientId) {
            $this->getClientData($notificationData);
        }
    }

    private function getClientData(NotificationData $notificationData) {
        if (empty($notificationData->clientData) && $notificationData->clientId) {
            $notificationData->clientData = $this->ucrmApi->query('clients/' . $notificationData->clientId);
        }
        return $notificationData->clientData;
    }

    private function getPaymentData(NotificationData $notificationData) {
        if (empty($notificationData->paymentData) && $notificationData->entityId) {
            $notificationData->paymentData = $this->ucrmApi->query('payments/' . $notificationData->entityId);
        }
        return $notificationData->paymentData;
    }
    
    private function getInvoiceData(NotificationData $notificationData) {
        if (empty($notificationData->invoiceData) && $notificationData->entityId) {
            $notificationData->invoiceData = $this->ucrmApi->query('invoices/' . $notificationData->entityId);
        }
        return $notificationData->invoiceData;
    }

    private function getServiceData(NotificationData $notificationData) {
        if (empty($notificationData->serviceData) && $notificationData->entityId) {
            $notificationData->serviceData = $this->ucrmApi->query('clients/services/' . $notificationData->entityId);
        }
        return $notificationData->serviceData;
    }
}
