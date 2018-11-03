<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Controllers;

use MVQN\Common\HTML;
use MVQN\Localization\Translator;
use MVQN\REST\UCRM\Endpoints\Client;
use MVQN\REST\UCRM\Endpoints\ClientContact;
use MVQN\REST\UCRM\Endpoints\Job;
use MVQN\REST\UCRM\Endpoints\Ticket;
use MVQN\REST\UCRM\Endpoints\TicketActivity;
use MVQN\REST\UCRM\Endpoints\TicketComment;
use MVQN\REST\UCRM\Endpoints\TicketGroup;
use MVQN\REST\UCRM\Endpoints\User;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

/**
 * Class DeleteEventController
 *
 * @package MVQN\UCRM\Plugins\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class DeleteEventController extends EventController
{
    /**
     * @param string $entity
     * @param int $entityId
     * @return EmailActionResult[]
     * @throws \Exception
     */
    public function action(string $entity, int $entityId): array
    {
        $results = [];
        $results["0"] = new EmailActionResult();
        $results["1"] = new EmailActionResult();

        // =============================================================================================================
        // RECIPIENTS
        // =============================================================================================================

        switch($entity)
        {
            case "client":  $recipients = Settings::getClientRecipients();  break;
            case "invoice": $recipients = Settings::getInvoiceRecipients(); break;
            case "payment": $recipients = Settings::getPaymentRecipients(); break;
            case "quote":   $recipients = Settings::getQuoteRecipients();   break;
            case "service": $recipients = Settings::getServiceRecipients(); break;
            case "ticket":  $recipients = Settings::getTicketRecipients();  break;
            case "user":    $recipients = Settings::getUserRecipients();    break;
            case "webhook": $recipients = Settings::getWebhookRecipients(); break;
            default:        $recipients = "";                               break;
        }

        array_map("trim", explode(",", $this->replaceVariables(
            $recipients,
            [
                //"TICKET_ASSIGNED_USER" => "",
            ],
            $results["0"]->recipients, // Static Recipients
            $results["1"]->recipients  // Dynamic Recipients
        )));

        $results["0"]->recipients = array_filter($results["0"]->recipients);
        $results["1"]->recipients = array_filter($results["1"]->recipients);

        // =============================================================================================================
        // DATA
        // =============================================================================================================

        // Build some view data to be passed to the Twig template.
        $viewData =
            [
                "personalized" => false,
                "entityId" => $entityId,
                "entity" => $entity,
            ];

        // =============================================================================================================
        // HTML
        // =============================================================================================================

        // Generate the HTML version of the email, then minify and reformat cleanly!
        $results["0"]->html = HTML::tidyHTML(HTML::minify($this->twig->render("$entity/delete.html.twig", $viewData)));

        // =============================================================================================================
        // TEXT
        // =============================================================================================================

        // Generate the TEXT version of the email, to be used as a fall back!
        $results["0"]->text = $this->twig->render("$entity/delete.text.twig", $viewData);

        // =============================================================================================================
        // SUBJECT
        // =============================================================================================================

        $results["0"]->subject = "A ".ucfirst($entity)." has been Deleted";

        // =============================================================================================================
        // RESULT
        // =============================================================================================================

        // Return the ActionResult!
        return $results;
    }

}