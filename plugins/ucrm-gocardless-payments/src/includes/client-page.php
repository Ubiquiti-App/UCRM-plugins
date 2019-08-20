<?php

if (!empty($_GET['client'])) {
  if (! $user || ! $user->isClient) {
    if (! headers_sent()) {
        header("HTTP/1.1 403 Forbidden");
    }
    die('You do not have permission to see this page.');
  }

  $handler = new \Ugpp\GocardlessHandler;
  $client = $handler->get('clients/'.$user->clientId);
  $Generator = new \Ugpp\Generator;
  $mandateToken = $Generator->getAttribute($client['attributes'], 'ucspGatewayToken');

  if ($_GET['client'] == 'initiate') {
    $response = $handler->initiateRedirectFlow($client);
  }

  include(PROJECT_PATH.'/includes/assets.php');

?>

<div class="container-fluid ugpp p-4">
  <div class="row">
    <div class="col-12">
      <img src="<?php echo $public_folder_path; ?>/gocardless_banner.jpg" class="logo-image">
      <h2 class="h5 my-3">Direct Debit with GoCardless</h2>
    </div>
    <div class="col-auto">
      <?php if ($_GET['client'] == 'initiate') { ?>
        A new tab will opened to a GoCardless signup page.<br><a href="<?php echo htmlspecialchars($response->redirect_url); ?>" target="_blank"><button class="btn btn-success mt-3">Proceed</button></a>
      <?php } else { ?>
        <?php if ($mandateToken) { ?>
          <p>You have setup Direct Debit and will be automatically charged when an invoice is due.</p>
          <a href="public.php?client=clear"><button class="btn btn-danger">Disable Auto Pay with Gocardless</button></a>
        <?php } else { ?>
          <a href="public.php?client=initiate"><button class="btn btn-primary">Enable Auto Pay with Gocardless</button></a>
        <?php } ?>
      <?php } ?>
    </div>
  </div>
</div>


<?php
  exit();
}