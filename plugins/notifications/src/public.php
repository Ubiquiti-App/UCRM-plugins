<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__."/bootstrap.php";

use MVQN\REST\UCRM\Endpoints\WebhookEvent;

use MVQN\UCRM\Plugins\Log;
use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

use MVQN\UCRM\Plugins\Controllers\EmailActionResult;
use MVQN\UCRM\Plugins\Controllers\DeleteEventController;
use MVQN\UCRM\Plugins\Controllers\ClientEventController;
use MVQN\UCRM\Plugins\Controllers\TicketEventController;
use MVQN\UCRM\Plugins\Controllers\TicketCommentEventController;

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
        $results = []; //  new EmailActionResult();

        $deleteController = new DeleteEventController($twig);

        // Handle the different Webhook Event types...
        switch ($entityType)
        {
            case "client":
                // Instantiate a new EventController and determine the correct type of action to take...
                $controller =               new ClientEventController($twig);
                switch ($changeType)
                {
                    case "insert":          $results = $controller->action("add", $entityId);                   break;
                    case "archive":         $results = $controller->action("archive", $entityId);               break;
                    case "delete":          $results = $deleteController->action("client", $entityId);          break;
                    case "edit":            $results = $controller->action("edit", $entityId);                  break;
                    case "invitation":      $results = $controller->action("invite", $entityId);                break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "invoice":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new InvoiceEventController($twig);
                switch ($changeType)
                {
                    case "insert":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "add_draft":       Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "delete":          $results = $deleteController->action("invoice", $entityId);         break;
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
                    case "insert":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "delete":          $results = $deleteController->action("payment", $entityId);         break;
                    case "edit":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "unmatch":         Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "quote":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new QuoteEventController($twig);
                switch ($changeType)
                {
                    case "insert":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "delete":          $results = $deleteController->action("quote", $entityId);           break;
                    case "edit":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "service":
                // Instantiate a new EventController and determine the correct type of action to take...
                //$controller =             new ServiceEventController($twig);
                switch ($changeType)
                {
                    case "insert":          Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "archive":         Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "edit":            Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "end":             Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "postpone":        Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "suspend":         Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    case "suspend_cancel":  Log::http("The Event: '$eventName' is not supported!", 501);        break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "ticketComment":
                // Instantiate a new EventController and determine the correct type of action to take...
                $controller =               new TicketCommentEventController($twig);
                switch ($changeType)
                {
                    case "comment":         $results = $controller->action("comment", $entityId);               break;
                    default:                Log::http("The Event: '$eventName' is not supported!", 501);        break;
                }   break;

            case "ticket":
                // Instantiate a new EventController and determine the correct type of action to take...
                $controller =               new TicketEventController($twig);
                switch ($changeType)
                {
                    case "insert":          $results = $controller->action("add", $entityId);                   break;
                    case "delete":          $results = $deleteController->action("ticket", $entityId);          break;
                    case "edit":            $results = $controller->action("edit", $entityId);                  break;
                    case "status_change":   $results = $controller->action("status_change", $entityId);         break;
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

        $verboseDebug = Settings::getVerboseDebug();

        echo "\n";

        // DEBUG: Echo any debug messages to the Webhook Request Log...
        if($verboseDebug)
        {
            print_r($results);
        }

        // Setup the mailer for our use here...
        try
        {
            // Initialize an instance of the mailer!
            $mail = new PHPMailer(true);

            //$mail->Debugoutput = "echo";
            //if($verboseDebug)
            //    $mail->SMTPDebug = 2;

            $mail->isSMTP();
            $mail->Host = Config::getSmtpHost();
            $mail->SMTPAuth = true;
            $mail->Username = Config::getSmtpUsername();
            $mail->Password = Config::getSmtpPassword();
            if (Config::getSmtpEncryption() !== "")
                $mail->SMTPSecure = Config::getSmtpEncryption();
            $mail->Port = Config::getSmtpPort();
            $mail->setFrom(Config::getSmtpSenderEmail());
            $mail->addReplyTo(Config::getSmtpSenderEmail());

            $mail->isHTML(Settings::getSmtpUseHTML());


            foreach($results as $result)
            {
                if($result->recipients === [])
                    continue;

                $mail->clearAddresses();

                print_r($result->recipients);

                foreach ($result->recipients as $email)
                    $mail->addAddress($email);

                $mail->Subject = $result->subject;
                $mail->Body = $result->html;
                $mail->AltBody = $result->text;

                // Finally, attempt to send the message!
                $mail->send();
            }

            if($verboseDebug)
                echo "\n";

            // IF we've made it this far, the message should have sent successfully, notify the system.
            Log::http("A valid Webhook Event was received and a notification message sent successfully!", 200);
        }
        catch (Exception $e)
        {
            if($verboseDebug)
                echo "\n";

            // OTHERWISE, something went wrong, so notify the system of failure.
            Log::http("A valid Webhook Event was received and a notification message sent successfully!\n{$mail->ErrorInfo}", 400);
        }
    }

    // SHOULD NEVER REACH HERE!

})();
