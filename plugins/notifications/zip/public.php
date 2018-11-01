<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";

use MVQN\Localization\Translator;

use MVQN\REST\UCRM\Endpoints\WebhookEvent;

use MVQN\UCRM\Plugins\Log;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;
use MVQN\UCRM\Plugins\Plugin;
use MVQN\UCRM\Plugins\Events\ClientEvent;
use MVQN\UCRM\Plugins\Events\TicketEvent;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * public.php
 *
 * Handles webhook events of any selected entity changes and then notifies the appropriate people as configured in the
 * plugin settings.
 *
 * Use an immediately invoked function here to prevent pollution of the global namespace.
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
(function()
{
    // Include the bootstrap.php configuration and setup file.
    require __DIR__."/bootstrap.php";

    // Parse the input received from Webhook events.
    $data = file_get_contents("php://input");
    Log::write("RECEIVED: ".$data);

    // Parse the JSON payload into an array for further handling.
    $dataArray = json_decode($data, true);

    // IF the array/payload is empty...
    if (!$dataArray) {
        // THEN return a "Bad Request" response and skip this event!
        http_response_code(400);
        Log::write("SKIPPING: The Webhook Event payload was empty!\n".$data);
        die("The Webhook Event payload was empty!\n".$data);
    }

    // Attempt to get the UUID from the payload.
    $uuid = array_key_exists("uuid", $dataArray) ? $dataArray["uuid"] : "";

    // IF the data does not include a valid UUID...
    if (!$uuid)
    {
        // THEN return a "Bad Request" response and skip this event!
        http_response_code(400);
        Log::write("SKIPPING: The Webhook Event payload did not contain a valid UUID field!\n$data");
        die("The Webhook Event payload did not contain a valid UUID field!\n$data");
    }

    // OTHERWISE, attempt to get the Webhook Event from the UCRM system for validation...
    $event = WebhookEvent::getByUuid($uuid);

    // IF the Webhook Event exists in the UCRM...
    if ($event->getUuid() === $uuid)
    {
        // THEN we should be good to continue!

        // Get the individual values from the payload.
        $changeType = $dataArray["changeType"]; // edit
        $entityType = $dataArray["entity"]; // client
        $entityId = $dataArray["entityId"]; // 1
        $eventName = $dataArray["eventName"]; // client.edit

        $results = [
            "html" => "",
            "text" => "",
            "recipients" => [],
            "subject" => ""
        ];

        // Handle the different Webhook Event types...
        switch ($entityType) {
            case "client":
                // Instantiate a new ClientEvent.
                $clientEvent = new ClientEvent($twig);

                switch ($eventName)
                {
                    case "client.add":
                        $results = $clientEvent->event("add", $entityId);
                        break;
                    case "client.archive":
                        $results = $clientEvent->event("archive", $entityId);
                        break;
                    // TODO: The Client at this point no longer exists, so how should we handle the notification???
                    // NOTE: Check with UBNT about firing this event before the client is actually deleted!
                    //case "client.delete":
                    //    $results = $clientEvent->event("delete", $entityId);
                    //    break;
                    case "client.edit":
                        $results = $clientEvent->event("edit", $entityId);
                        break;
                    case "client.invite":
                        $results = $clientEvent->event("invite", $entityId);
                        break;

                    default:
                        http_response_code(200);
                        Log::write("SKIPPING: The Webhook Event: '$eventName' is not currently supported!");
                        die("The Webhook Event: '$eventName' is not currently supported!");
                        break;
                }
                break;

            case "ticket":
            case "ticketComment":

                $ticketEvent = new TicketEvent($twig);

                switch ($eventName)
                {
                    case "ticket.add":
                        $results = $ticketEvent->event("add", $entityId);
                        break;
                    case "ticket.comment":
                        $results = $ticketEvent->event("comment", $entityId);
                        break;
                    // TODO: The Ticket at this point no longer exists, so how should we handle the notification???
                    // NOTE: Check with UBNT about firing this event before the client is actually deleted!
                    //case "ticket.delete":
                    //    $results = $ticketEvent->event("delete", $entityId);
                    //    break;
                    case "ticket.edit":
                        $results = $ticketEvent->event("edit", $entityId);
                        break;
                    case "ticket.status_change":
                        $results = $ticketEvent->event("status_change", $entityId);
                        break;

                    default:
                        http_response_code(200);
                        Log::write("SKIPPING: The Webhook Event: '$eventName' is not currently supported!");
                        die("The Webhook Event: '$eventName' is not currently supported!");
                        break;
                }
                break;


            // TODO: Add the other event types as needed!!!

            default:
                http_response_code(200);
                Log::write("SKIPPING: The Webhook Event Type: '$entityType' is not currently supported!");
                die("The Webhook Event Type: '$entityType' is not currently supported!");
        }

        // Initialize an instance of the mailer!
        $mail = new PHPMailer(true);

        // Setup the mailer for our use here...
        try {
            //$mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host = Config::getSmtpHost();
            $mail->SMTPAuth = true;
            $mail->Username = Config::getSmtpUsername();
            $mail->Password = Config::getSmtpPassword();
            if (Config::getSmtpEncryption() !== "")
                $mail->SMTPSecure = Config::getSmtpEncryption();
            $mail->Port = Config::getSmtpPort();
            $mail->setFrom(Config::getSmtpSenderEmail());

            foreach ($results["recipients"] as $email)
                $mail->addAddress($email);

            $mail->addReplyTo(Config::getSmtpSenderEmail());

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

            $mail->isHTML(Settings::getSmtpUseHTML());
            $mail->Subject = $results["subject"];
            $mail->Body = $results["html"];
            $mail->AltBody = $results["text"];

            // Finally, attempt to send the message!
            $mail->send();

            // IF we've made it this far, the message should have sent successfully, notify the system.
            http_response_code(200);
            Log::write("SUCCESS : A valid Webhook Event was received and a notification message sent successfully!");
            die("A valid Webhook Event was received and a notification message sent successfully!");
        }
        catch (Exception $e)
        {
            // OTHERWISE, something went wrong, so notify the system of failure.
            http_response_code(400);
            Log::write("ERROR   : Message could not be sent, Mailer Error: '{$mail->ErrorInfo}'");
            die("Message could not be sent, Mailer Error: '{$mail->ErrorInfo}'");
        }
    }

    // SHOULD NEVER REACH HERE!

})();
