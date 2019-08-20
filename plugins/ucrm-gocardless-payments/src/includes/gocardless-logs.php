<?php
if (isset($_GET['admin'])) {

  if ($_GET['admin'] == 'gocardless-logs') {
    // Check Authentication
    $ucrmSecurity = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();
    $user = $ucrmSecurity->getUser();
    $url = $options->ucrmPublicUrl . "system/plugins/" . $options->pluginId . "/public?parameters%5Badmin%5D=gocardless-logs";

    if (! $user || ! $user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SYSTEM_PLUGINS)) {
      if (! headers_sent()) {
          header("HTTP/1.1 403 Forbidden");
      }
      die('You do not have permission to see this page.');
    }
    if (file_exists(PROJECT_PATH.'/data/gocardless.log')) {
      if (isset($_GET['clear-logs'])) {
        file_put_contents(PROJECT_PATH.'/data/gocardless.log', '');
# PHP cannot redirect into parent window use javascript to avoid window duplicates ?>
<script type="text/javascript">

  parent.window.location='<?php echo $url . "&parameters%5Bcleared%5D=true" ?>';
</script>
<?php
        // header("Location: ". $url . "&parameters%5Bcleared%5D=true");
        
        die();
      }

      $logs = file_get_contents(PROJECT_PATH.'/data/gocardless.log');

  ?>
  
  <?php if (isset($_GET['cleared'])) { ?>
    <div class="notification">
      Logs Cleared
    </div>
  <?php } ?>

  <div class="wrapper">
    <a href="https://www.charuwts.com/plugins/ucrm-signup" target="_blank"><img src="https://s3.amazonaws.com/charuwts.com/images/charuwts-logo.png" class="fit-image logo-image"></a>
    <h3 class="mt-3">UCRM GoCardless Payments Plugin</h3>
    <p>This log is for specific errors and processes and will also be improved in the future to have filters for types of log information. Some logs are also reported on the plugin details page.</p>
    <a href="<?php echo $url . "&parameters%5Bclear-logs%5D=true" ?>" target="_parent" class="btn btn-primary">Clear Logs</a>
    <pre>
      <div class="code-wrapper">
        <?php print_r($logs); ?>
      </div>
    </pre>
  </div>
    <style type="text/css">
      body {
        margin: 0;
      }
      .code-wrapper {
        background-color: #EEE;
        max-width: 100%;
        width: 1000px;
        overflow: auto;
        padding: 20px;
      }
      .logo-image {
        width: 400px;
      }
      .fit-image {
        display: block;
        max-width: 100%;
      }
      h3 {
        font-size: 1.2rem;
      }
      .wrapper {
        background-color: white;
        padding: 20px;
      }
      .notification {
        width: 100%;
        background-color: rgba(64, 176, 83, 0.6);
        padding: 0.8rem;
      }
      .btn-primary {
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
        cursor: pointer;
      }
      .btn {
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: .375rem .75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: .25rem;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
      }
    </style>
<?php
  } else {
    echo 'Nothing to report.';
  }

    exit();
  }
}

