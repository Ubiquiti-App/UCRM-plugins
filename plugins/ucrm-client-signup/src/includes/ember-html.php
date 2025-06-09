<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $options->ucrmPublicUrl; ?> - Signup</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="ucrm-client-signup-form/config/environment" content="%7B%22modulePrefix%22%3A%22ucrm-client-signup-form%22%2C%22environment%22%3A%22production%22%2C%22rootURL%22%3A%22/%22%2C%22locationType%22%3A%22none%22%2C%22EmberENV%22%3A%7B%22FEATURES%22%3A%7B%7D%2C%22EXTEND_PROTOTYPES%22%3A%7B%22Date%22%3Afalse%7D%7D%2C%22APP%22%3A%7B%22rootElement%22%3A%22%23ember-signup%22%2C%22host%22%3A%22<?php echo $options->pluginPublicUrl; ?>%22%2C%22completionText%22%3A%22<?php echo rawurlencode((string) $config['COMPLETION_TEXT']); ?>%22%2C%22frontendKey%22%3A%22<?php echo \Ucsp\Interpreter::getFrontendKey() . '||' . ($_SESSION['csrf_token'] ?? ''); ?>%22%2C%22isLead%22%3A%22<?php echo $config['LEAD'] ? 'yes' : 'no'; ?>%22%2C%22name%22%3A%22ucrm-client-signup-form%22%2C%22version%22%3A%221.0.0+5acad376%22%7D%2C%22exportApplicationGlobal%22%3Afalse%7D" />

      <?php $publicUrl = str_replace('.php', '/', $options->pluginPublicUrl); ?>
      <link rel="stylesheet" type="text/css" href="<?php echo $publicUrl . 'vendor-d3aa84b783735f00b7be359e81298bf2.css' ?>">
      <link rel="stylesheet" type="text/css" href="<?php echo $publicUrl . 'ucrm-client-signup-form-883f793171e4b4cc98b9a928918a9189.css' ?>">
  </head>
  <body>
    <?php if (! empty($config['LOGO_URL'])) { ?>
      <img src="<?php echo htmlspecialchars($config['LOGO_URL'], ENT_QUOTES); ?>" class="logo">
    <?php } ?>

    <?php if (! empty($config['FORM_TITLE'])) { ?>
      <h1 class="text-center mt-2"><?php echo htmlspecialchars($config['FORM_TITLE'], ENT_QUOTES); ?></h1>
    <?php } ?>

    <br clear="all">
    <?php if (! empty($config['FORM_DESCRIPTION'])) { ?>
      <div class="form-description">
        <?php echo htmlspecialchars($config['FORM_DESCRIPTION'], ENT_QUOTES); ?>
      </div>
      <br clear="all">
    <?php } ?>
    
    <div id="ember-signup"></div>
    <script type="text/javascript" src="<?php echo $publicUrl . 'vendor-2c628552e172e7331ad9cef9845edbd3.js'; ?>"></script>
    <script type="text/javascript" src="<?php echo $publicUrl . 'ucrm-client-signup-form-1203e7d52840a7fa255aec93c1355211.js'; ?>"></script>

    <div id="ember-bootstrap-wormhole"></div>
    <div id="ember-basic-dropdown-wormhole"></div>
  </body>
</html>
