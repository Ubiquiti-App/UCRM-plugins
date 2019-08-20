<?php
if (isset($_GET['admin'])) {

  if ($_GET['admin'] == 'manage-clients') {
    // Check Authentication
    $ucrmSecurity = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();
    $user = $ucrmSecurity->getUser();

    if (! $user || ! $user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SYSTEM_PLUGINS)) {
      if (! headers_sent()) {
          header("HTTP/1.1 403 Forbidden");
      }
      die('You do not have permission to see this page.');
    }
    
    $handler = new \Ugpp\GocardlessHandler;

    // Ajax Action Handling
    if (!empty($_POST['action']) && !empty($_POST['action_value'])) {
      if ($_POST['action'] === 'unlink') {
        $handler->unlink($_POST['action_value']);
        echo 'Client '.$_POST['action_value'].' unlinked from GoCardless.';
      }
      exit();
    }

    // Generate custom attributes if needed
    $Generator = new \Ugpp\Generator();
    $Generator->createCustomAttributes();

    include(PROJECT_PATH.'/includes/assets.php');
  ?>

  <!-- ### // ### -->
  <!-- HEADER HTML >> -->
  <nav class="container-fluid ugpp">
    <div class="row border-bottom py-3 align-items-center">
      <div class="col-7">
        <img src="https://s3.amazonaws.com/charuwts.com/images/charuwts-logo.png" class="fit-image logo-image"> 
      </div>
      <div class="col-5 text-right">
        <img src="<?php echo $public_folder_path; ?>/gocardless-blue-rgb_2018_lrg.png" class="pr-4" style="max-width: 200px;">
      </div>
    </div>
  </nav>
  <!-- << HEADER HTML -->
  <!-- ### // ### -->

  <!-- ### // ### -->
  <!-- Get Client and Get customer if exists >> -->
  <?php
    
    if (!empty($_GET['clientId'])) {
      $client = $handler->get('clients/'.$_GET['clientId']);

      $customer = $handler->getCustomer($client);
      $ucspGatewayTokenId = $Generator->getAttribute($client['attributes'], 'ucspGatewayToken');
  ?>
  <!-- Get Client and Get customer if exists << -->
  <!-- ### // ### -->

  <?php include(PROJECT_PATH.'/includes/components/ucrm-menu.php'); ?>

  <!-- ### // ### -->
  <!-- Redirect Flow >> -->
  <?php
      // initiate GoCardless redirect flow  
      if (!empty($_GET['initiateFlow'])) {
        $response = $handler->initiateRedirectFlow($client);
  ?>

        <div class="container-fluid">
          <?php  $username = $client['firstName'] . ' ' .$client['lastName']; ?>
          <p class="my-3">A new tab will be opened to a GoCardless signup page for <?php echo htmlspecialchars($username) ?>.</p>
          <a href="?admin=manage-clients&clientId=<?php echo htmlspecialchars($_GET['clientId']) ?>"><button class="btn btn-primary mt-3 mx-1">Back to Client</button></a><a href="<?php echo htmlspecialchars($response->redirect_url); ?>" target="_blank"><button class="btn btn-success mt-3 mx-1">Proceed to GoCardless</button></a>
        </div>

  <?php
        exit();  
      }
  ?>
  <!-- Redirect Flow << -->
  <!-- ### // ### -->

    <div class="container-fluid">
      <div class="row py-3 border-bottom align-items-center">
        <h4 class="col-12">
          UCRM Client:<b> <?php echo $client['id'] . ' - ' . $client['firstName'] . ' ' .$client['lastName']; ?></b>
        </h4>
        <?php if ($ucspGatewayTokenId) { ?>
          <div class="col-md-auto my-2">
            <h4>Mandate ID: <b><?php echo $ucspGatewayTokenId; ?></b></h4>
          </div>
        <?php } else { ?>
          <div class="col-12">
            <a href="?admin=manage-clients&initiateFlow=true&clientId=<?php echo htmlspecialchars($client['id']); ?>"><button class="btn btn-primary" name="link">Initiate GoCardless Setup</button></a>
          </div>
        <?php } ?>

      </div>
    </div>

    
  <!-- ### // ### -->
  <!-- GoCardless Customer >> -->
  <?php
    if ($customer) { 
  ?>
    <div class="container-fluid gocardless-details px-4">
      <div class="row border-bottom py-2 align-items-center">
        <div class="col-auto">
          <h4>GoCardless Customer Info</h4>
        </div>
        <div class="col-auto justify-self-end">
          <button class="action-btn btn btn-primary" name="unlink" value="<?php htmlspecialchars($client['id']); ?>">Unlink GoCardless</button>
        </div>
        <div class="col-12 pt-2">
          <p class="small font-italic">Note: Currently 'unlinking' GoCardless simply clears the custom attributes associated with GoCardless from the UCRM client. This effectively prevents UCRM from communicating further with the Gateway customer but it does not disable or cancel anything already on GoCardless, this will have to be done from the GoCardless Dashboard.</p>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <?php if ($customer->id) { echo '<b>Customer ID</b>: '. $customer->id; } ?>
          <?php if ($customer->id) { echo '<br>'; } ?>
          <?php if ($customer->given_name) { echo '<b>Given Name</b>: '. $customer->given_name; } else { echo 'Company Name: ' . $customer->company_name; } ?>
          <?php if ($customer->family_name) { echo '<br><b>Family Name</b>: '. $customer->family_name; } ?>
          <?php if ($customer->email) { echo '<br><b>Email</b>: '. $customer->email; } ?>
          <?php # if ($customer->phone_number) { echo '<br><b>Phone Number</b>: '. $customer->phone_number; } ?>
          <?php if ($customer->address_line1) { echo '<br><b>Address Line 1</b>: '. $customer->address_line1; } ?>
          <?php if ($customer->address_line2) { echo '<br><b>Address Line 2</b>: '. $customer->address_line2; } ?>
          <?php if ($customer->address_line3) { echo '<br><b>Address Line 3</b>: '. $customer->address_line3; } ?>
          <?php if ($customer->city) { echo '<br><b>City</b>: '. $customer->city; } ?>
          <?php if ($customer->postal_code) { echo '<br><b>Postal Code</b>: '. $customer->postal_code; } ?>
          <?php if ($customer->country_code) { echo '<br><b>Country Code</b>: '. $customer->country_code; } ?>
          <?php if ($customer->danish_identity_number) { echo '<br><b>Danish Identity Number</b>: '. $customer->danish_identity_number; } ?>
          <?php if ($customer->swedish_identity_number) { echo '<br><b>Swedish Identity Number</b>: '. $customer->swedish_identity_number; } ?>
          
        </div>
      </div>
    </div>
  <?php } else if ($ucspGatewayTokenId && !$customer) { ?>
    <div class="col-md-auto my-2">
      Gateway Customer has not been set, this can be manually done on the client's custom attributes or you can link GoCardless again by overwriting both the Mandate ID and the Customer ID.
      <a href="?admin=manage-clients&initiateFlow=true&clientId=<?php echo htmlspecialchars($client['id']); ?>"><button class="btn btn-primary" name="link">Initiate GoCardless Setup</button></a>
    </div>
  <?php } ?>
  <?php  
    } else {
  ?>
  <!-- GoCardless Customer << -->
  <!-- ### // ### -->
  <?php 
    $limit      = ( isset( $_GET['limit'] ) ) ? $_GET['limit'] : 10;
    $page       = ( isset( $_GET['page'] ) ) ? $_GET['page'] : 1;
    $links      = ( isset( $_GET['links'] ) ) ? $_GET['links'] : 7;
    $Paginator  = new Paginator();
    $clients    = $Paginator->getData( $limit, $page );
  ?>

  <div class="container-fluid px-4">
    <div class="row py-4 mb-3 border-bottom">
      <div class="col-auto"><input type="number" id="userIdValue" name="user-id" class="form-control" placeholder="Get by ID"></div>
      <div class="col-auto"><button class="form-control btn btn-primary" id="search">Get Client</button></div>
      <div class="col-auto">
        <?php echo $Paginator->createLinks( $links, 'pagination' ); ?>
      </div>
      <div class="col-auto">
        <ul class="pagination">
          <li class="page-item"><a class="page-link" href="#">Show:</a></li>
          <li class="page-item"><a class="page-link" href="<?php echo '?admin=manage-clients&limit=10&page=1'; ?>">10</a></li>
          <li class="page-item"><a class="page-link" href="<?php echo '?admin=manage-clients&limit=20&page=1'; ?>">20</a></li>
          <li class="page-item"><a class="page-link" href="<?php echo '?admin=manage-clients&limit=50&page=1'; ?>">50</a></li>
          <li class="page-item"><a class="page-link" href="<?php echo '?admin=manage-clients&limit=100&page=1'; ?>">100</a></li>
        </ul>
      </div>
    </div>

       

    <table class="table table-striped table-condensed table-bordered table-rounded">
      <thead>
              <tr>
              <th>ID</th>
              <th width="20%">Name</th>
              <th width="20%">Mandate Status</th>
              <th width="25%">Action</th>
      </tr>
      </thead>
      <tbody>
        <?php for( $i = 0; $i < count( $clients->data ); $i++ ) : ?>
        <?php
          $gatewayCustomerId = $Generator->getAttribute($clients->data[$i]['attributes'], 'ucspGatewayCustomer');
          $ucspGatewayTokenId = $Generator->getAttribute($clients->data[$i]['attributes'], 'ucspGatewayToken');
          if ($gatewayCustomerId) {
            $customer = $handler->gocardless_api->customers()->get($gatewayCustomerId);
          } else {
            $customer = false;
          }

          if ($ucspGatewayTokenId) {
            $mandate = $handler->gocardless_api->mandates()->get($ucspGatewayTokenId);
          } else {
            $mandate = false;
          }
        ?>

          <tr>
            <td><a href="?admin=manage-clients&clientId=<?php echo htmlspecialchars($clients->data[$i]['id']) ?>"><?php echo $clients->data[$i]['id'] ?></a></td>
            <td>
              <?php if (!empty($clients->data[$i]['companyContactFirstName'])) { ?>
                <?php echo $clients->data[$i]['companyContactFirstName'] ?> <?php echo $clients->data[$i]['companyContactLastName'] ?>
              <?php } else if (!empty($clients->data[$i]['companyName'])) { ?>
                <?php echo $clients->data[$i]['companyName'] ?>
              <?php } else { ?>
                <?php echo $clients->data[$i]['firstName'] ?> <?php echo $clients->data[$i]['lastName'] ?>
              <?php } ?>
            </td>
            <td>
              <?php if (!$mandate) { ?>
                N/A
              <?php } else { ?>
                <?php echo $mandate->status;?>
              <?php } ?>
            </td>
            <td>
              <?php if (!$mandate) { ?>
                <a href="?admin=manage-clients&initiateFlow=true&clientId=<?php echo htmlspecialchars($clients->data[$i]['id']); ?>"><button class="btn btn-primary" name="link">Initiate GoCardless Setup</button></a>
              <?php } else { ?>
                <button class="action-btn btn btn-danger" name="unlink" value="<?php echo htmlspecialchars($clients->data[$i]['id']); ?>">Unlink GoCardless</button>
              <?php } ?>
            </td>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>

  <?php  } ?>
  <script type="text/javascript">
    $(document).ready(function(){
        $('body').find('#search').click(function() {
          window.location = '?admin=manage-clients&clientId='+$('#userIdValue').val();
        });
        $('body').find('.action-btn').click(function(){
            var button = $(this);
            var clickBtnName = $(this).attr("name");
            var value = $(this).val();
            // alert("action performed "+ clickBtnName);
            var ajaxurl = window.location,
            data =  {'action': clickBtnName, 'action_value': value};

            $.post(ajaxurl, data, function (response) {
              button.addClass('no-events');
              button.removeClass('btn-primary');
              button.addClass('btn-light');
              button.text('Unlinked');
              alert(response);
            });
        });
    });
  </script>
  <?php  
    exit();
  }
  
}