<?php

chdir(__DIR__);

define("PROJECT_PATH", __DIR__);

require_once(PROJECT_PATH.'/src/initialize.php');

// ## Get JSON from post request
$payload = @file_get_contents("php://input");
$payload_decoded = json_decode($payload);

// ## Only run if app key exists
if (!empty($payload_decoded->pluginAppKey)) {
  // ## Instantiate handler
  $handler = new UCS\UcrmHandler;
  
  // ## If payload includes client
  if (!empty($payload_decoded->client)) {
    // ## Create Client
    $handler->createClient($payload_decoded->client, true);
  
  // ## If payload has countries - countries == true
  } elseif (!empty($payload_decoded->countries)) {
    // ## Return countries
    echo json_response($handler->getCountries(), 200, true);
  
  // ## If payload has country_id
  } elseif (!empty($payload_decoded->country_id)) {
    // ## Return countries
    echo json_response($handler->getStatesByCountry($payload_decoded->country_id), 200, true);
  }
  
  // ## Else, return form
} else {
  ?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo UCRM_PUBLIC_URL; ?> - Signup</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="ucrm-client-signup-form/config/environment" content="%7B%22modulePrefix%22%3A%22ucrm-client-signup-form%22%2C%22environment%22%3A%22production%22%2C%22rootURL%22%3A%22/%22%2C%22locationType%22%3A%22none%22%2C%22EmberENV%22%3A%7B%22FEATURES%22%3A%7B%7D%2C%22EXTEND_PROTOTYPES%22%3A%7B%22Date%22%3Afalse%7D%7D%2C%22APP%22%3A%7B%22rootElement%22%3A%22%23ember-signup%22%2C%22host%22%3A%22<?php echo PLUGIN_PUBLIC_URL; ?>%22%2C%22completionText%22%3A%22<?php echo rawurlencode((string)UCS\Config::$COMPLETION_TEXT); ?>%22%2C%22pluginAppKey%22%3A%22<?php echo FRONTEND_PUBLIC_KEY; ?>%22%2C%22name%22%3A%22ucrm-client-signup-form%22%2C%22version%22%3A%221.0.0+bdc8ead4%22%7D%2C%22exportApplicationGlobal%22%3Afalse%7D" />

    <style type="text/css">
      <?php // ## UCRM requires file paths, Using PHP include instead of HTML tags to avoid relative URL ?>
      <?php include(PROJECT_PATH."/assets/vendor-d3aa84b783735f00b7be359e81298bf2.css"); ?>
      <?php include(PROJECT_PATH."/assets/ucrm-client-signup-form-883f793171e4b4cc98b9a928918a9189.css"); ?>
    </style>
    
  </head>
  <body>
    <?php if (!empty(UCS\Config::$LOGO_URL)) { ?>
      <img src="<?php echo UCS\Config::$LOGO_URL; ?>" class="logo">
    <?php } ?>

    <?php if (!empty(UCS\Config::$FORM_TITLE)) { ?>
      <h1 class="text-center mt-2"><?php echo UCS\Config::$FORM_TITLE; ?></h1>
    <?php } ?>

    <br clear="all">
    <?php if (!empty(UCS\Config::$FORM_DESCRIPTION)) { ?>
      <div class="form-description">
        <?php echo UCS\Config::$FORM_DESCRIPTION; ?>
      </div>
      <br clear="all">
    <?php } ?>
    
    <div id="ember-signup"></div>
    <script type="text/javascript">
      <?php // ## UCRM requires file paths, Using PHP include instead of HTML tags to avoid relative URL ?>
      <?php include(PROJECT_PATH."/assets/vendor-2c628552e172e7331ad9cef9845edbd3.js"); ?>
      <?php include(PROJECT_PATH."/assets/ucrm-client-signup-form-3240a881afb08a23df5e1ae7a7da5661.js"); ?>
    </script>

    <div id="ember-bootstrap-wormhole"></div>
  </body>
</html>

<?php
}
?>