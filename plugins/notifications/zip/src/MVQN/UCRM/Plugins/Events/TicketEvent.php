<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Events;

use MVQN\Common\HTML;
use MVQN\Localization\Translator;
use MVQN\UCRM\Plugins\Log;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

use MVQN\UCRM\Plugins\Exceptions\PluginNotInitializedException;

use MVQN\REST\UCRM\Endpoints\Ticket;
use MVQN\REST\UCRM\Endpoints\User;
use MVQN\REST\UCRM\Endpoints\Client;
use MVQN\REST\UCRM\Endpoints\ClientContact;


/**
 * Class TicketEvent
 *
 * @package MVQN\UCRM\Plugins\Events
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class TicketEvent
{
    /** @var \Twig_Environment */
    protected $twig;

    /** @var array */
    //protected $translations;

    /**
     * ClientEvent constructor.
     *
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        //$this->translations = $translations;
    }

    /**
     * @param string $event
     * @param int $entityId
     * @return array
     * @throws PluginNotInitializedException
     * @throws \MVQN\Localization\Exceptions\DictionaryException
     * @throws \MVQN\Localization\Exceptions\TranslatorException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function event(string $event, int $entityId): array
    {
        $results = [
            "html" => "",
            "text" => "",
            "recipients" => [],
            "subject" => "",
            "debug" => ""
        ];

        /** @var Ticket $ticket */
        $ticket = Ticket::getById($entityId);
        $clientId = $ticket->getClientId();

        /** @var Client|null $client Get the actual Client from the UCRM. */
        $client = $clientId !== null ? Client::getById($clientId) : null;

        /** @var ClientContact[] $contacts Get the actual Client Contacts from the UCRM. */
        $contacts = $client !== null ? $client->getContacts()->elements() : null;

        /** @var User $user */
        $user = $ticket->getAssignedUserId() ? User::getById($ticket->getAssignedUserId()) : null;

        // TODO: Add TicketGroup endpoint!
        //$group = $ticket->getAssignedGroupId() ? TicketGroup::getById($ticket->getAssignedGroupId()) : null;

        $latestComment = $ticket->getActivity()->where("type", "comment")->last();

        // Build some view data to be passed to the Twig template.
        $viewData =
            [
                //"translations" => $this->translations,
                "ticket" => $ticket,
                "user" => $user,
                "latestComment" => $latestComment,
                "client" => $client,
                "contacts" => $contacts,
                "url" => Settings::UCRM_PUBLIC_URL,
                "googleMapsApiKey" => Config::getGoogleApiKey() ?: "",
            ];

        // Generate the HTML version of the email, then minify and reformat cleanly!
        $results["html"] = HTML::tidyHTML(HTML::minify($this->twig->render("ticket/$event.html.twig", $viewData)));

        // Generate the TEXT version of the email, to be used as a fall back!
        $results["text"] = $this->twig->render("ticket/$event.text.twig", $viewData);

        $recipientsString = Settings::getTicketRecipients();

        // IF the recipients list is not NULL and not empty...
        if($recipientsString !== "" && $recipientsString !== null)
        {
            // THEN handle parsing the recipients.

            // Split the list by commas and create the recipients array.
            $recipients = array_map("trim", explode(",", $recipientsString));

            // IF the recipients list does NOT contain any variables...
            if(strpos($recipientsString, "%") !== false)
            {
                // OTHERWISE, we need to parse the variables also.

                $results["recipients"] = [];

                // Loop through each recipient in the list...
                foreach($recipients as $recipient)
                {
                    $variable = preg_match("/^%(.+)%$/", $recipient, $matches);

                    if($matches !== null && count($matches) > 0)
                    {
                        switch($matches[1])
                        {
                            case "TICKET_ASSIGNED_USER":
                                $results["recipients"][] = $user->getEmail(); // Email is a required field of User!
                                break;

                            // TODO: Add other variables as needed!

                            default:
                                Log::warning("An unknown variable '{$matches[1]}' was encountered in the Ticket "
                                    ."Recipients field of the plugin Settings!");
                                break;
                        }
                    }
                    else
                    {
                        $results["recipients"][] = $recipient;
                    }

                }

            }
            else
            {
                // THEN simply provide the recipients array!
                $results["recipients"][] = $recipients;
                //$results["debug"] = "NONE";
            }


        }

        // Set the appropriate subject line for this notification.
        $subject = "Ticket";

        switch ($event)
        {
            case "add": $subject .= " Added"; break;
            case "comment": $subject .= " Commented"; break;
            case "delete": $subject .= " Deleted"; break;
            case "edit": $subject .= " Edited"; break;
            case "status_change": $subject .= " Changed"; break;
        }

        $results["subject"] = Translator::learn($subject);

        // Should be ready to send...

        return $results;
    }






}