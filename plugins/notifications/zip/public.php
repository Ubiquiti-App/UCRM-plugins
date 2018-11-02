<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__."/bootstrap.php";

use MVQN\REST\UCRM\Endpoints\WebhookEvent;

use MVQN\UCRM\Plugins\Log;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

use MVQN\UCRM\Plugins\Controllers\EmailActionResult;
use MVQN\UCRM\Plugins\Controllers\ClientEventController;
use MVQN\UCRM\Plugins\Controllers\TicketEventController;

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
(function() use ($twig)
{
    // Parse the input received from Webhook events.
    $data = file_get_contents("php://input");
    //Log::write("RECEIVED: ".$data);

    // Parse the JSON payload into an array for further handling.
    $dataArray = json_decode($data, true);

    // IF the array/payload is empty, THEN return a "Bad Request" response and skip this event!
    if (!$dataArray)
        Log::http("The Webhook Event payload was empty!\n$data", 400);

    // Attempt to get the UUID from the payload.
    $uuid = array_key_exists("uuid", $dataArray) ? $dataArray["uuid"] : "";

    // IF the data does not include a valid UUID, THEN return a "Bad Request" response and skip this event!
    if (!$uuid)
        Log::http("The Webhook Event payload did not contain a valid UUID field!\n$data", 400);

    // OTHERWISE, attempt to get the Webhook Event from the UCRM system for validation...
    $event = WebhookEvent::getByUuid($uuid);

    // IF the Webhook Event exists in the UCRM...
    if ($event->getUuid() === $uuid)
    {
        // THEN we should be good to continue, as this is our verification of a valid event!

        // Get the individual values from the payload.
        $changeType = $dataArray["changeType"]; // edit
        $entityType = $dataArray["entity"]; // client
        $entityId = $dataArray["entityId"]; // 1
        $eventName = $dataArray["eventName"]; // client.edit

        // Create a new EmailActionResult to store our rendered template and other data.
        $result = new EmailActionResult();

        // Handle the different Webhook Event types...
        switch ($entityType)
        {
            case "client":
                // Instantiate a new EventController and determine the correct type of action to take...
                $controller =               new ClientEventController($twig);
                switch ($changeType)
                {
                    case "add":             $result = $controller->action("add", $entityId);                    break;
                    case "archive":         $result = $controller->action("archive", $entityId);                break;
                    case "delete":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "edit":            $result = $controller->action("edit", $entityId);                   break;
                    case "invite":          $result = $controller->action("invite", $entityId);                 break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "invoice":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new InvoiceEventController($twig);
                switch ($changeType)
                {
                    case "add":             Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "add_draft":       Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "delete":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "edit":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "near_due":        Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "overdue":         Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "payment":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new PaymentEventController($twig);
                switch ($changeType)
                {
                    case "add":             Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "delete":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "edit":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "unmatch":         Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "quote":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new QuoteEventController($twig);
                switch ($changeType)
                {
                    case "add":             Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "delete":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "edit":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "service":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new ServiceEventController($twig);
                switch ($changeType)
                {
                    case "add":             Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "archive":         Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "edit":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "end":             Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "postpone":        Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "suspend":         Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "suspend_cancel":  Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "ticket":
                // Instantiate a new EventController and determine the correct type of action to take...
                $controller =               new TicketEventController($twig);
                switch ($changeType)
                {
                    case "add":             $result = $controller->action("add", $entityId);                    break;
                    case "delete":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "edit":            $result = $controller->action("edit", $entityId);                   break;
                    case "status_change":   $result = $controller->action("status_change", $entityId);          break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "ticketComment":
                // Instantiate a new EventController and determine the correct type of action to take...
                $controller =               new TicketEventController($twig);
                switch ($changeType)
                {
                    case "comment":         $result = $controller->action("comment", $entityId);                break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "user":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new UserEventController($twig);
                switch ($changeType)
                {
                    case "reset_password":  Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "webhook":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new WebhookEventController($twig);
                switch ($changeType)
                {
                    case "test":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            default:                        Log::http("The Entity: '$entityType' is not supported!", 501);      break;
        }

        // DEBUG: Echo any debug messages to the Webhook Request Log...
        $result->echoDebug();

        // Setup the mailer for our use here...
        try
        {
            // Initialize an instance of the mailer!
            $mail = new PHPMailer(true);

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



            //echo Settings::getTicketRecipients()."\n";
            //echo count($results["recipients"])."\n";

            foreach ($result->recipients as $email)
            {
                //echo "$email\n";
                $mail->addAddress($email);
            }

            //echo "*** ";
            //echo print_r($mail->getToAddresses());
            //echo "\n";

            $mail->addReplyTo(Config::getSmtpSenderEmail());

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

            $mail->isHTML(Settings::getSmtpUseHTML());
            $mail->Subject = $result->subject;
            $mail->Body = $result->html;
            $mail->AltBody = $result->text;

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
