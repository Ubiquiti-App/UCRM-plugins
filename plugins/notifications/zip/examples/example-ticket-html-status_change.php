<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/bootstrap.php";

use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

use MVQN\REST\UCRM\Endpoints\Ticket;
use MVQN\REST\UCRM\Endpoints\User;
use MVQN\REST\UCRM\Endpoints\Client;
use MVQN\REST\UCRM\Endpoints\ClientContact;

/** @var Ticket $ticket */
$ticket = Ticket::getById(9);
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
        //"translations" => $translations,
        "ticket" => $ticket,
        "user" => $user,
        "latestComment" => $latestComment,
        "client" => $client,
        "contacts" => $contacts,
        "url" => Settings::UCRM_PUBLIC_URL,
        "googleMapsApiKey" => Config::getGoogleApiKey() ?: "",
    ];

// Generate the HTML version of the email, then minify and reformat cleanly!
echo $twig->render("ticket/status_change.html.twig", $viewData);