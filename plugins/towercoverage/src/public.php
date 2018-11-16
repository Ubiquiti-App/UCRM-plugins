<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__."/bootstrap.php";

use UCRM\Common\Log;

use UCRM\Plugins\Settings;

use UCRM\Plugins\Data\TowerCoverage;

use UCRM\REST\Endpoints\ClientLog;
use UCRM\REST\Endpoints\Client;
use UCRM\REST\Endpoints\ClientContact;

use UCRM\REST\Endpoints\State;
use UCRM\REST\Endpoints\Country;

/**
 * public.php
 *
 * Handles XML push data received from TowerCoverage.com.
 *
 * Use an immediately invoked function here to prevent pollution of the global namespace.
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
(function()
{
    // Parse the input received from TowerCoverage.com.
    $dataRaw = file_get_contents("php://input");
    //Log::info("RECEIVED: ".$dataRaw);

    // Used for testing!
    if(!$dataRaw)
        $dataRaw = file_get_contents(__DIR__."/examples/push-data.xml");

    try {
        // Attempt to parse the XML payload.
        $json = json_encode(new \SimpleXMLElement($dataRaw, LIBXML_NOCDATA));
        $data = json_decode($json, true);

        // Attempt to convert the payload to a TowerCoverage object.
        $towerCoverage = new TowerCoverage($data);

        // Get the CustomerDetails section, as that is the most useful here!
        $customerDetails = $towerCoverage->getCustomerDetails();

        $apiKey = $customerDetails->getApiKey();
        $apiUsername = $customerDetails->getUsername();
        $apiPassword = $customerDetails->getPassword();

        if (Settings::getApiKey() !== null && Settings::getApiKey() !== $apiKey)
            Log::http("API Key does not match the one set in this Plugin's Settings.", 401);

        if (Settings::getApiUsername() !== null && Settings::getApiUsername() !== $apiUsername)
            Log::http("API Username does not match the one set in this Plugin's Settings.", 401);

        if (Settings::getApiPassword() !== null && Settings::getApiPassword() !== $apiPassword)
            Log::http("API Password does not match the one set in this Plugin's Settings.", 401);

        Log::info("Valid TowerCoverage data has been received!");

        /** @var Client|null $existingClient */
        $existingClient = null;

        switch (Settings::getDuplicateMode()) {
            case "":
                $existingResidentialLeads = Client::getLeadsOnly()->whereAll([
                    "clientType" => Client::CLIENT_TYPE_RESIDENTIAL,
                    "firstName" => $customerDetails->getFirstName(),
                    "lastName" => $customerDetails->getLastName(),
                ]);

                $existingClient = $existingResidentialLeads->first();
                break;

            case "email":
                $existingResidentialLeads = Client::getLeadsOnly()->whereAll([
                    "clientType" => Client::CLIENT_TYPE_RESIDENTIAL
                ]);

                /** @var Client $client */
                foreach ($existingResidentialLeads as $client) {
                    /** @var ClientContact|null $contact */
                    $contact = $client->getContacts()->first();

                    if ($contact !== null && $contact->getEmail() === $customerDetails->getEmailAddress()) {
                        $existingClient = $client;
                        break;
                    }
                }
                break;

            case "street":
                $existingResidentialLeads = Client::getLeadsOnly()->whereAll([
                    "clientType" => Client::CLIENT_TYPE_RESIDENTIAL
                ]);

                /** @var Client $client */
                foreach ($existingResidentialLeads as $client) {
                    $street = $client->getStreet1();

                    if ($street !== null && $street !== "" && $street === $customerDetails->getStreetAddress()) {
                        $existingClient = $client;
                        break;
                    }
                }
                break;

            default:
                Log::http("The Duplicate Mode: '" . Settings::getDuplicateMode() . "' is not implemented!", 500);
                break;
        }

        $clientExists = $existingClient !== null;

        /** @var Client $client */
        $client = $clientExists ? $existingClient : Client::createResidentialLead("", "");

        // Get the Country and then State for later use.
        $country = Country::getByName($customerDetails->getCountry());
        $state = State::getByName($country, $customerDetails->getState());

        $note = implode("\n", $customerDetails->getComments());

        // Update the Client's information.
        $client
            ->setFirstName($customerDetails->getFirstName())
            ->setLastName($customerDetails->getLastName())
            ->setAddress(
                $customerDetails->getStreetAddress(),
                $customerDetails->getCity(),
                $state->getCode(),
                $country->getCode(),
                $customerDetails->getZip())
            ->setAddressGpsLat($customerDetails->getCustomerLat())
            ->setAddressGpsLon($customerDetails->getCustomerLong())
            ->setNote($note);

        /** @var Client $upsertedClient */
        $upsertedClient = ($clientExists) ? $client->update() : $client->insert();


        $contacts = $upsertedClient->getContacts();

        $contactExists = ($contacts->count() !== 0);

        /** @var ClientContact $contact */
        $contact = ($contacts->count() === 0 ?
            new ClientContact(["clientId" => $upsertedClient->getId()]) : $contacts->first())
            ->setName($customerDetails->getFirstName() . " " . $customerDetails->getLastName())
            ->setEmail($customerDetails->getEmailAddress())
            ->setPhone($customerDetails->getPhoneNumber());

        /**
         * @noinspection PhpUnusedLocalVariableInspection
         * @var ClientContact $upsertedContact
         */
        $upsertedContact = ($contacts->count() === 0) ? $contact->insert() : $contact->update();

        $log = new ClientLog([ "clientId" => $upsertedClient->getId() ]);
        $log->setCreatedDate(new DateTime());

        if ($clientExists)
        {
            $message = "Client".($contactExists ? " & Contact" : "")." Updated by TowerCoverage EUS Submission!";

            $log->setMessage($message);
            $log->insert();

            Log::http("Client & Contact Updated Successfully!", 200);
        }
        else
        {
            $message = "Client".(!$contactExists ? " & Contact" : "")." Created by TowerCoverage EUS Submission!";

            $log->setMessage($message);
            $log->insert();

            Log::http($message, 201);
        }

    }


    catch(\Exception $e)
    {
        //Log::http("Invalid TowerCoverage.com data received: ".json_encode($dataRaw, JSON_UNESCAPED_SLASHES), 400);
        Log::http("Invalid TowerCoverage.com data received: ".$e->getMessage(), 400);
    }



    http_response_code(200);



})();
