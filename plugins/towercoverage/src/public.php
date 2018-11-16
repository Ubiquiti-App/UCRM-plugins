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

    // IF there has been no data received...
    if(!$dataRaw)
    {
        // AND we are in development mode...
        if(Settings::getDevelopment())
            // THEN load some sample data to use for parsing.
            $dataRaw = file_get_contents(__DIR__."/examples/push-data.xml");
        else
            // OTHERWISE return a HTTP 400 - Bad Request and die()!
            Log::http("Invalid TowerCoverage data has been received!", 400);
    }

    // Attempt to parse the XML payload...
    try
    {
        // ---------------------------------------------------------------------------------------------------------
        // SUBMISSION DATA
        // ---------------------------------------------------------------------------------------------------------

        // Encode the XML data into JSON, being sure to keep the CDATA nodes.
        $json = json_encode(new \SimpleXMLElement($dataRaw, LIBXML_NOCDATA));

        // Then decode the JSON data back into an associative array.
        $data = json_decode($json, true);

        // Attempt to convert the payload to a TowerCoverage object.
        $towerCoverage = new TowerCoverage($data);

        // Get the CustomerDetails section, as that is the only useful node!
        $customerDetails = $towerCoverage->getCustomerDetails();

        // Get the API Key, Username and Password values (or NULL) from the CustomerDetails.
        $apiKey = $customerDetails->getApiKey();
        $apiUsername = $customerDetails->getUsername();
        $apiPassword = $customerDetails->getPassword();

        // IF the Plugin Settings contain an API Key AND it does NOT match the parsed value, THEN return Unauthorized!
        if (Settings::getApiKey() !== null && Settings::getApiKey() !== $apiKey)
            Log::http("API Key does not match the one set in this Plugin's Settings.", 401);

        // IF the Plugin Settings contain a Username AND it does NOT match the parsed value, THEN return Unauthorized!
        if (Settings::getApiUsername() !== null && Settings::getApiUsername() !== $apiUsername)
            Log::http("API Username does not match the one set in this Plugin's Settings.", 401);

        // IF the Plugin Settings contain a Password AND it does NOT match the parsed value, THEN return Unauthorized!
        if (Settings::getApiPassword() !== null && Settings::getApiPassword() !== $apiPassword)
            Log::http("API Password does not match the one set in this Plugin's Settings.", 401);

        Log::info("Valid TowerCoverage data has been received!");

        /** @var Client|null $existingClient */
        $existingClient = null;

        // =============================================================================================================
        // SUBMISSION DE-DUPLICATION
        // =============================================================================================================

        // Check the Plugin Settings for the current "Duplicate Mode"
        switch (Settings::getDuplicateMode())
        {
            // ---------------------------------------------------------------------------------------------------------
            // PRIMARY EMAIL
            // ---------------------------------------------------------------------------------------------------------
            case "email":

                // Get all Client Leads from the UCRM where the Client Type is Residential...
                $existingResidentialLeads = Client::getLeadsOnly()->whereAll([
                    "clientType" => Client::CLIENT_TYPE_RESIDENTIAL
                ]);

                // Loop through each filtered Client Lead returned from the UCRM...
                foreach ($existingResidentialLeads as /** @var Client $client */ $client)
                {
                    // Get the Primary Contact from each Client Lead.

                    /** @var ClientContact|null $contact */
                    $contact = $client->getContacts()->first();

                    // IF the Email Address for this Client Lead is the same as the one from the submission...
                    if ($contact !== null &&  $contact->getEmail() === $customerDetails->getEmailAddress())
                    {
                        // THEN consider it matched and the Existing Client Lead.
                        $existingClient = $client;
                        break;
                    }
                }
                break;

            // ---------------------------------------------------------------------------------------------------------
            // STREET ADDRESS
            // ---------------------------------------------------------------------------------------------------------
            case "street":

                // Get all Client Leads from the UCRM where the Client Type is Residential...
                $existingResidentialLeads = Client::getLeadsOnly()->whereAll([
                    "clientType" => Client::CLIENT_TYPE_RESIDENTIAL
                ]);

                // Loop through each filtered Client Lead returned from the UCRM...
                foreach ($existingResidentialLeads as /** @var Client $client */ $client)
                {
                    // Get the Street Address from each Client Lead.
                    $street = $client->getStreet1();

                    // IF the Street Address for this Client Lead is the same as the one from the submission...
                    if ($street !== null && $street !== "" && $street === $customerDetails->getStreetAddress())
                    {
                        // THEN consider it matched and the Existing Client Lead.
                        $existingClient = $client;
                        break;
                    }
                }
                break;

            // ---------------------------------------------------------------------------------------------------------
            // FIRST & LAST NAMES (DEFAULT)
            // ---------------------------------------------------------------------------------------------------------
            case "":
            default:
                // Get all Client Leads from the UCRM where the Client Type is Residential AND both the First and Last
                // Names match the ones from the submission...
                $existingResidentialLeads = Client::getLeadsOnly()->whereAll([
                    "clientType" => Client::CLIENT_TYPE_RESIDENTIAL,
                    "firstName" => $customerDetails->getFirstName(),
                    "lastName" => $customerDetails->getLastName(),
                ]);

                // Either a matched Client Lead or NULL will be set to the Existing Client Lead.
                $existingClient = $existingResidentialLeads->first();
                break;
        }

        // =============================================================================================================
        // CLIENT LEAD INSERT/UPDATE
        // =============================================================================================================

        // Set a bool flag indicating whether or not an existing Client Lead was found.
        $clientExists = $existingClient !== null;

        // And then assign either the existing Client Lead or a newly created one...

        /** @var Client $client */
        $client = $clientExists ? $existingClient : Client::createResidentialLead("", "");

        // NOTE: All Client Leads are currently created with a type of "Residential", as there is no easy way to
        // determine otherwise from the TowerCoverage EUS Form.

        // Get the Country and then State for later use.
        $country = Country::getByName($customerDetails->getCountry());
        $state = State::getByName($country, $customerDetails->getState());

        // Get any provided comments to be set as the Client Lead's notes.
        $note = $customerDetails->getComments();

        // Insert/Update the Client's information...
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

        // Attempt to insert/update the Client Lead in the UCRM...

        /** @var Client $upsertedClient */
        $upsertedClient = ($clientExists) ? $client->update() : $client->insert();

        // NOTE: In the case of a non-existent Client Lead, the insertion MUST be done first to get a valid Client ID
        // which will be needed when inserting the new Contact information.

        // Get any existing Contacts from the upserted Client Lead.
        $contacts = $upsertedClient->getContacts();

        // Set a bool flag indicating whether or not an existing set of Contacts was found.
        $contactExists = ($contacts->count() !== 0);

        // And then assign either the existing primary Contact or a newly created one...

        /** @var ClientContact $contact */
        $contact = ($contacts->count() === 0 ?
            new ClientContact(["clientId" => $upsertedClient->getId()]) : $contacts->first())
            ->setName($customerDetails->getFirstName() . " " . $customerDetails->getLastName())
            ->setEmail($customerDetails->getEmailAddress())
            ->setPhone($customerDetails->getPhoneNumber());

        // Attempt to insert/update the Contact in the UCRM...

        /**
         * @noinspection PhpUnusedLocalVariableInspection
         * @var ClientContact $upsertedContact
         */
        $upsertedContact = ($contacts->count() === 0) ? $contact->insert() : $contact->update();

        // NOTE: In the case of a non-existent Contact, the insertion MUST be done first to get a valid Contact ID
        // which will be needed when inserting the new Client Log information.

        // Create a new Client Log entry and set it's timestamp to NOW.
        $log = new ClientLog([ "clientId" => $upsertedClient->getId() ]);
        $log->setCreatedDate(new DateTime());

        // IF the Client Lead already existed...
        if ($clientExists)
        {
            // THEN generate and insert a Client Log message indicating the Client Lead was updated.
            $message = "Client".($contactExists ? " & Contact" : "")." Updated by TowerCoverage EUS Submission!";
            $log->setMessage($message)->insert();

            // Return HTTP 200 - OK
            Log::http($message, 200);
        }
        else
        {
            // OTHERWISE generate and insert a Client Log message indicating the Client Lead was created.
            $message = "Client".(!$contactExists ? " & Contact" : "")." Created by TowerCoverage EUS Submission!";
            $log->setMessage($message)->insert();

            // Return HTTP 201 - Created
            Log::http($message, 201);
        }

    }
    catch(\Exception $e)
    {
        // When an Exception is caught, Log the error and return HTTP 400 - Bad Request!
        Log::http("Invalid TowerCoverage.com data received: ".$e->getMessage(), 400);
    }

    // WE SHOULD NEVER REACH THIS LINE!!!

})();
