<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Controllers;

use MVQN\Common\HTML;
use MVQN\Localization\Translator;
use MVQN\REST\UCRM\Endpoints\Client;
use MVQN\REST\UCRM\Endpoints\ClientContact;
use MVQN\REST\UCRM\Endpoints\Ticket;
use MVQN\REST\UCRM\Endpoints\TicketComment;
use MVQN\REST\UCRM\Endpoints\TicketGroup;
use MVQN\REST\UCRM\Endpoints\User;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

/**
 * Class TicketEventController
 *
 * @package MVQN\UCRM\Plugins\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class TicketEventController extends EventController
{
    /**
     * @param string $action
     * @param int $ticketId
     * @return EmailActionResult
     * @throws \Exception
     */
    public function action(string $action, int $ticketId): EmailActionResult
    {
        $result = new EmailActionResult();

        // =============================================================================================================
        // DATA
        // =============================================================================================================

        /** @var Ticket $ticket */
        $ticket = Ticket::getById($ticketId);

        /** @var TicketGroup|null $group */
        $group = TicketGroup::getById($ticket->getAssignedGroupId());

        /** @var User|null $user */
        $user = User::getById($ticket->getAssignedUserId());

        /** @var TicketComment|null $latestComment */
        $latestComment = $ticket->getActivity()->where("type", "comment")->last();

        /** @var Client|null $client */
        $client = Client::getById($ticket->getClientId());

        /** @var ClientContact[] $contacts */
        $contacts = $client->getContacts()->elements();

        // Build some view data to be passed to the Twig template.
        $viewData =
            [
                "ticket" => $ticket,
                "group" => $group,
                "user" => $user,
                "latestComment" => $latestComment,
                "client" => $client,
                "contacts" => $contacts,
                "url" => Settings::UCRM_PUBLIC_URL,
                "googleMapsApiKey" => Config::getGoogleApiKey() ?: "",
            ];

        // =============================================================================================================
        // HTML
        // =============================================================================================================

        // Generate the HTML version of the email, then minify and reformat cleanly!
        $result->html = HTML::tidyHTML(HTML::minify($this->twig->render("ticket/$action.html.twig", $viewData)));

        // =============================================================================================================
        // TEXT
        // =============================================================================================================

        // Generate the TEXT version of the email, to be used as a fall back!
        $result->text = $this->twig->render("ticket/$action.text.twig", $viewData);

        // =============================================================================================================
        // RECIPIENTS
        // =============================================================================================================

        $result->recipients = explode(",", $this->replaceVariables(Settings::getTicketRecipients(),
            [
                "TICKET_ASSIGNED_USER" => $user->getEmail()
            ]
        ));

        // =============================================================================================================
        // SUBJECT
        // =============================================================================================================

        // Set the appropriate subject line for this notification.
        $subject = "Ticket";

        switch ($action)
        {
            case "add": $subject .= " Added"; break;
            case "comment": $subject .= " Commented"; break;
            case "delete": $subject .= " Deleted"; break;
            case "edit": $subject .= " Edited"; break;
            case "status_change": $subject .= " Changed"; break;
        }

        $result->subject = Translator::learn($subject);

        // =============================================================================================================
        // RESULT
        // =============================================================================================================

        // Return the ActionResult!
        return $result;
    }

}