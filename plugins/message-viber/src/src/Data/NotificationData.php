<?php
declare(strict_types=1);
namespace ViberNotifier\Data;

/*
 * data received in webhook, plus details loaded from UCRM API
 */
class NotificationData
{
    /** @var string */
    public $uuid;
    /** @var string */
    public $changeType;

    /** @var string */
    public $entity;

    /** @var int|null */
    public $entityId;

    /** @var int|null */
    public $clientId;

    /** @var string */
    public $eventName;

    /** @var array|null */
    public $clientData;

    /** @var array|null */
    public $serviceData;

    /** @var array|null */
    public $invoiceData;

    /** @var array|null */
    public $paymentData;
}