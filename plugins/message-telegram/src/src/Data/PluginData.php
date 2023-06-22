<?php

declare(strict_types=1);


namespace TelegramNotifier\Data;

/*
 * data entered in plugin's config
 */
class PluginData extends UcrmData
{
    public $telegramBotToken;
    public $displayedErrors;
    
    public $event_client_add;
    public $event_client_archive;
    public $event_client_delete;
    public $event_client_edit;
    public $event_invoice_add;
    public $event_invoice_add_draft;
    public $event_invoice_draft_approved;
    public $event_invoice_delete;
    public $event_invoice_edit;
    public $event_payment_add;
    public $event_payment_delete;
    public $event_payment_edit;
    public $event_payment_unmatch;
    public $event_service_activate;
    public $event_service_add;
    public $event_service_archive;
    public $event_service_end;
    public $event_service_postpone;
    public $event_service_suspend_cancel;
    public $event_service_suspend;
    public $event_invoice_near_due;
    public $event_invoice_overdue;
    public $logging_level;
}
