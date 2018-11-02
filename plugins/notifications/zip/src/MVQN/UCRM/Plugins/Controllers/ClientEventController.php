<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Controllers;

use MVQN\Common\HTML;
use MVQN\Localization\Translator;
use MVQN\REST\UCRM\Endpoints\Client;
use MVQN\REST\UCRM\Endpoints\ClientContact;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

/**
 * Class ClientEventController
 *
 * @package MVQN\UCRM\Plugins\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class ClientEventController extends EventController
{
    /**
     * @param string $action
     * @param int $clientId
     * @return EmailActionResult
     * @throws \Exception
     */
    public function action(string $action, int $clientId): EmailActionResult
    {
        $result = new EmailActionResult();

        // =============================================================================================================
        // DATA
        // =============================================================================================================

        /** @var Client|null $client */
        $client = Client::getById($clientId);

        /** @var ClientContact[] $contacts */
        $contacts = $client->getContacts()->elements();

        // Build some view data to be passed to the Twig template.
        $viewData =
            [
                "client" => $client,
                "contacts" => $contacts,
                "url" => Settings::UCRM_PUBLIC_URL,
                "googleMapsApiKey" => Config::getGoogleApiKey() ?: "",
            ];

        // =============================================================================================================
        // HTML
        // =============================================================================================================

        // Generate the HTML version of the email, then minify and reformat cleanly!
        $result->html = HTML::tidyHTML(HTML::minify($this->twig->render("client/$action.html.twig", $viewData)));

        // =============================================================================================================
        // TEXT
        // =============================================================================================================

        // Generate the TEXT version of the email, to be used as a fall back!
        $result->text = $this->twig->render("client/$action.text.twig", $viewData);

        // =============================================================================================================
        // RECIPIENTS
        // =============================================================================================================

        $result->recipients = explode(",", $this->replaceVariables(Settings::getClientRecipients(),
            [
                //"TICKET_ASSIGNED_USER" => $user->getEmail()
            ]
        ));

        // =============================================================================================================
        // SUBJECT
        // =============================================================================================================

        // Set the appropriate subject line for this notification.
        $subject = "Client";

        switch ($action)
        {
            case "add": $subject .= " Added"; break;
            case "archive": $subject .= " Archived"; break;
            case "delete": $subject .= " Deleted"; break;
            case "edit": $subject .= " Edited"; break;
            case "invite": $subject .= " Invited"; break;
        }

        $result->subject = Translator::learn($subject);

        // =============================================================================================================
        // RESULT
        // =============================================================================================================

        // Return the ActionResult!
        return $result;
    }

}