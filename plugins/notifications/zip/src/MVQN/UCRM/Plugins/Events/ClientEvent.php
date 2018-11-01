<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Events;

use MVQN\Common\HTML;
use MVQN\Localization\Translator;
use MVQN\UCRM\Plugins\Log;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

use MVQN\UCRM\Plugins\Exceptions\PluginNotInitializedException;

use MVQN\REST\UCRM\Endpoints\Client;
use MVQN\REST\UCRM\Endpoints\ClientContact;

/**
 * Class ClientEvent
 *
 * @package MVQN\UCRM\Plugins\Events
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class ClientEvent
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
            "subject" => ""
        ];

        /** @var Client $client Get the actual Client from the UCRM. */
        $client = Client::getById($entityId);

        // IF the Webhook Event is for a Client Lead, but "Clients  Only" is selected...
        if ($client->getIsLead() && Settings::getClientTypes() === "clients")
        {
            // THEN log and skip this Webhook Event!
            Log::write("SKIPPING: The Webhook Event targeted a Client Lead and 'Client Only?' was selected!");
            die("The Webhook Event targeted a Client Lead and 'Client Only?' was selected!");
        }

        // IF the Webhook Event is for a Client, but "Leads Only" is selected...
        if (!$client->getIsLead() && Settings::getClientTypes() === "leads")
        {
            // THEN log and skip this Webhook Event!
            Log::write("SKIPPING: The Webhook Event targeted a Client and 'Leads Only?' was selected!");
            die("The Webhook Event targeted a Client and 'Leads Only?' was selected!");
        }

        /** @var ClientContact[] $contacts Get the actual Client Contacts from the UCRM. */
        $contacts = $client->getContacts()->elements();

        // Build some view data to be passed to the Twig template.
        $viewData =
            [
                //"translations" => $this->translations,
                "client" => $client,
                "contacts" => $contacts,
                "url" => Settings::UCRM_PUBLIC_URL,
                "googleMapsApiKey" => Config::getGoogleApiKey() ?: "",
            ];

        // Generate the HTML version of the email, then minify and reformat cleanly!
        $results["html"] = HTML::tidyHTML(HTML::minify($this->twig->render("client/$event.html.twig", $viewData)));

        // Generate the TEXT version of the email, to be used as a fall back!
        $results["text"] = $this->twig->render("client/$event.text.twig", $viewData);

        // Set the appropriate set of recipients for this notification.
        if(Settings::getClientRecipients() !== "" && Settings::getClientRecipients() !== null)
            $results["recipients"] = explode(",", Settings::getClientRecipients());
        //else
        //    $results["recipients"] = ["rspaeth@mvqn.net"];

        // Set the appropriate subject line for this notification.
        $subject = $client->getIsLead() ? "Client Lead" : "Client";

        switch ($event)
        {
            case "add": $subject .= " Added"; break;
            case "archive": $subject .= " Archived"; break;
            case "delete": $subject .= " Deleted"; break;
            case "edit": $subject .= " Edited"; break;
            case "invite": $subject .= " Invited"; break;
        }

        $results["subject"] = Translator::learn($subject);

        // Should be ready to send...

        return $results;
    }






}