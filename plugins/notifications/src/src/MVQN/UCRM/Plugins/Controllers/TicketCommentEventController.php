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
 * Class TicketCommentEventController
 *
 * @package MVQN\UCRM\Plugins\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class TicketCommentEventController extends EventController
{
    /**
     * @param string $action
     * @param int $ticketCommentId
     * @return EmailActionResult[]
     * @throws \Exception
     */
    public function action(string $action, int $ticketCommentId): array
    {
        $results = [];
        $results["0"] = new EmailActionResult();
        $results["1"] = new EmailActionResult();

        // =============================================================================================================
        // DATA
        // =============================================================================================================

        /** @var TicketComment $comment */
        $comment = TicketComment::getById($ticketCommentId);

        /** @var Ticket $ticket */
        $ticket = Ticket::getById($comment->getTicketId());
        $results["0"]->debug[] = "Ticket\n".json_encode($ticket, JSON_PRETTY_PRINT)."\n";

        /** @var TicketGroup|null $group */
        $group = TicketGroup::getById($ticket->getAssignedGroupId());
        $results["0"]->debug[] = "Ticket Group\n".json_encode($group, JSON_PRETTY_PRINT)."\n";

        /** @var User|null $user */
        $user = User::getById($ticket->getAssignedUserId());
        $results["0"]->debug[] = "User\n".json_encode($user, JSON_PRETTY_PRINT)."\n";

        /** @var TicketComment|null $latestComment */
        $latestComment = $ticket->getActivity()->where("type", "comment")->last();
        $results["0"]->debug[] = "Latest Comment\n".json_encode($latestComment, JSON_PRETTY_PRINT)."\n";

        /** @var Client|null $client */
        $client = Client::getById($ticket->getClientId());
        $results["0"]->debug[] = "Client\n".json_encode($client, JSON_PRETTY_PRINT)."\n";

        /** @var ClientContact[] $contacts */
        $contacts = $client !== null ? $client->getContacts()->elements() : null;
        $results["0"]->debug[] = "Contacts\n".json_encode($contacts, JSON_PRETTY_PRINT)."\n";

        // LAST ACTIVITY
        /** @var TicketActivity $lastActivity */
        $lastActivity = $ticket->getActivity()->last();
        $results["0"]->debug[] = "Last Activity\n".json_encode($lastActivity, JSON_PRETTY_PRINT)."\n";

        $results["1"] = clone $results["0"];

        // =============================================================================================================
        // RECIPIENTS
        // =============================================================================================================

        /*
        switch($lastActivity->getType())
        {
            case "assignment_job":

                $job = Job::getById($lastActivity->getJobAssignment()->getAssignedJobId());


                $jobUser = User::getById($job->getAssignedUserId());

                array_map("trim", explode(",", $this->replaceVariables(
                    Settings::getTicketJobRecipients(),
                    [
                        "JOB_ASSIGNED_USER" => $jobUser->getEmail(),
                    ],
                    $results["0"]->recipients, // Static Recipients
                    $results["1"]->recipients  // Dynamic Recipients
                )));
                break;

            default:
                array_map("trim", explode(",", $this->replaceVariables(
                    Settings::getTicketRecipients(),
                    [
                        "TICKET_ASSIGNED_USER" => $user !== null ? $user->getEmail() : "",
                    ],
                    $results["0"]->recipients, // Static Recipients
                    $results["1"]->recipients  // Dynamic Recipients
                )));
                break;
        }
        */

        array_map("trim", explode(",", $this->replaceVariables(
            Settings::getTicketRecipients(),
            [
                "TICKET_ASSIGNED_USER" => $user !== null ? $user->getEmail() : "",
            ],
            $results["0"]->recipients, // Static Recipients
            $results["1"]->recipients  // Dynamic Recipients
        )));

        $results["0"]->recipients = array_filter($results["0"]->recipients);
        $results["1"]->recipients = array_filter($results["1"]->recipients);

        $results["0"]->debug[] = "Recipients\n".json_encode($results["0"]->recipients, JSON_PRETTY_PRINT)."\n";
        $results["1"]->debug[] = "Recipients\n".json_encode($results["1"]->recipients, JSON_PRETTY_PRINT)."\n";

        // Build some view data to be passed to the Twig template.
        $viewData =
            [
                "personalized" => false,
                "ticket" => $ticket,
                "group" => $group,
                "user" => $user,
                "latestComment" => $latestComment,
                "activity" => $lastActivity,
                "client" => $client,
                "contacts" => $contacts,
                "url" => Settings::UCRM_PUBLIC_URL,
                "googleMapsApiKey" => Config::getGoogleApiKey() ?: "",
            ];

        // =============================================================================================================
        // HTML
        // =============================================================================================================

        // Generate the HTML version of the email, then minify and reformat cleanly!
        $results["0"]->html = HTML::tidyHTML(HTML::minify($this->twig->render("ticket/$action.html.twig", $viewData)));
        $viewData["personalized"] = true;
        $results["1"]->html = HTML::tidyHTML(HTML::minify($this->twig->render("ticket/$action.html.twig", $viewData)));
        $viewData["personalized"] = false;

        // =============================================================================================================
        // TEXT
        // =============================================================================================================

        // Generate the TEXT version of the email, to be used as a fall back!
        $results["0"]->text = $this->twig->render("ticket/$action.text.twig", $viewData);
        $viewData["personalized"] = true;
        $results["1"]->text = $this->twig->render("ticket/$action.text.twig", $viewData);
        $viewData["personalized"] = false;

        // =============================================================================================================
        // SUBJECT
        // =============================================================================================================

        // Set the appropriate subject line for this notification.
        switch ($action)
        {
            /*
            case "add":
                $results["0"]->subject = "Ticket Added";
                $results["1"]->subject = "Ticket Added";
                break;
            */
            case "comment":
                $results["0"]->subject = "A Ticket has received a Comment";
                $results["1"]->subject = "A Ticket assigned to You has received a Comment";
                break;
            /*
            case "delete":
                $results["0"]->subject = "Ticket Deleted";
                $results["1"]->subject = "Ticket Deleted";
                break;
            case "edit":
                switch($lastActivity->getType())
                {
                    case "assignment":
                        $results["0"]->subject = "A Ticket has been assigned to a User";
                        $results["1"]->subject = "A Ticket has been assigned to You";
                        break;
                    case "assignment_client":
                        $results["0"]->subject = "A Ticket has been assigned to a Client";
                        $results["1"]->subject = "A Ticket Assigned to You has been assigned to a Client";
                        break;
                    case "assignment_job":
                        $results["0"]->subject = "A Ticket has been assigned to a Job";
                        $results["1"]->subject = "A Ticket Assigned to You has been assigned to a Job";
                        break;
                    default:
                        $results["0"]->subject = "Ticket Edited";
                        $results["1"]->subject = "Ticket Edited";
                        break;
                }
                break;
            case "status_change":
                $results["0"]->subject = "Ticket Changed";
                $results["1"]->subject = "Ticket Changed";
                break;
            */
            default:
                $results["0"]->subject = "";
                $results["1"]->subject = "";
                break;
        }

        $results["0"]->subject = Translator::learn($results["0"]->subject);
        $results["1"]->subject = Translator::learn($results["1"]->subject);

        $results["0"]->debug[] = "Subject\n".json_encode($results["0"]->subject, JSON_PRETTY_PRINT)."\n";
        $results["1"]->debug[] = "Subject\n".json_encode($results["1"]->subject, JSON_PRETTY_PRINT)."\n";

        // =============================================================================================================
        // RESULT
        // =============================================================================================================

        // Return the ActionResult!
        return $results;
    }

}