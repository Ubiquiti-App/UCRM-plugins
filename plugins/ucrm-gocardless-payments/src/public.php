<?php
require_once __DIR__ . '/vendor/autoload.php';
chdir(__DIR__);
define("PROJECT_PATH", __DIR__);

include(PROJECT_PATH.'/includes/initialize.php');
include(PROJECT_PATH.'/includes/client-page.php');
include(PROJECT_PATH.'/includes/admin-page.php');
include(PROJECT_PATH.'/includes/gocardless-logs.php');


if (!empty($_GET['redirect_flow_id']) && !empty($_GET['clientId'])) {
  if (empty($_COOKIE['PHPSESSID'])) {
    echo "Invalid Session ID...";
  } else { 
    try {
      $GcClient = new \Ugpp\GocardlessHandler;
      
      $redirectFlow = $GcClient->gocardless_api->redirectFlows()->complete(
          $_GET['redirect_flow_id'],
          ["params" => ["session_token" => $_COOKIE['PHPSESSID']]]
      );
      
      $GcClient->link($_GET['clientId'], $redirectFlow->links->customer, $redirectFlow->links->mandate);

      header("LOCATION: ".$redirectFlow->confirmation_url);
    } catch (\GoCardlessPro\Core\Exception\InvalidApiUsageException $e) {
      echo 'There was an application error.';
      file_put_contents(PROJECT_PATH."/data/plugin.log", $e->getMessage(), FILE_APPEND);
    } catch (\GoCardlessPro\Core\Exception\InvalidStateException | \GoCardlessPro\Core\Exception\ValidationFailedException $e) {
      echo $e->getMessage();
    }
  }

}

$headers = getallheaders();
if (!empty($headers["Webhook-Signature"])) {
  $signature_header = $headers["Webhook-Signature"];
  
  if ($signature_header) {
    $request_body = file_get_contents('php://input');
    $handler = new \Ugpp\GocardlessHandler; 
    echo $handler->handleWebhook($request_body, $signature_header);
  }
}
