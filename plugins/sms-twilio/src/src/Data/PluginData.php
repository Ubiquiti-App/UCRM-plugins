<?php

declare(strict_types=1);


namespace SmsNotifier\Data;

/*
 * data entered in plugin's config
 */
class PluginData extends UcrmData
{
    /**
     * @var string
     */
    public $twilioAccountSid;

    /**
     * @var string
     */
    public $twilioAuthToken;

    /**
     * @var string
     */
    public $twilioSmsNumber;

    /**
     * @var string
     */
    public $displayedErrors;

    /**
     * @var string
     */
    public $event_client_add;

    /**
     * @var string
     */
    public $event_client_archive;

    /**
     * @var string
     */
    public $event_client_delete;

    /**
     * @var string
     */
    public $event_client_edit;

    /**
     * @var string
     */
    public $event_invoice_add;

    /**
     * @var string
     */
    public $event_invoice_add_draft;

    /**
     * @var string
     */
    public $event_invoice_draft_approved;

    /**
     * @var string
     */
    public $event_invoice_delete;

    /**
     * @var string
     */
    public $event_invoice_edit;

    /**
     * @var string
     */
    public $event_payment_add;

    /**
     * @var string
     */
    public $event_payment_delete;

    /**
     * @var string
     */
    public $event_payment_edit;

    /**
     * @var string
     */
    public $event_payment_unmatch;

    /**
     * @var string
     */
    public $event_service_activate;

    /**
     * @var string
     */
    public $event_service_add;

    /**
     * @var string
     */
    public $event_service_archive;

    /**
     * @var string
     */
    public $event_service_end;

    /**
     * @var string
     */
    public $event_service_postpone;

    /**
     * @var string
     */
    public $event_service_suspend_cancel;

    /**
     * @var string
     */
    public $event_service_suspend;

    /**
     * @var string
     */
    public $event_invoice_near_due;

    /**
     * @var string
     */
    public $event_invoice_overdue;

}
