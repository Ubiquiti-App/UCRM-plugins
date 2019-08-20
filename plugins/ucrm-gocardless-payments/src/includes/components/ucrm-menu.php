<!-- <link rel="stylesheet" type="text/css" href="<#?php echo $public_folder_path; ?>/ucrm-main-copy-001.css"> -->
<?php $url = $options->ucrmPublicUrl . "system/plugins/" . $options->pluginId . "/public?parameters%5Badmin%5D=manage-clients"; ?>
<div id="ucrm-menu">
<!--   <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col">
        <h1><a href="/client">UCRM Clients</a> / <a href="/client/<?php echo $client['id']; ?>" class="primary"><?php echo htmlspecialchars($client['firstName']) . ' ' . htmlspecialchars($client['lastName']); ?></a></h1>
      </div>
      <div class="col-auto"><a href="/client/<?php echo $client['id']; ?>/edit" class="btn btn-dark py-0">Edit client</a></div>
    </div>
  </div>
 -->  
  <div class="menu-items">
    <ul>
      <li><a href="<?php echo $url; ?>" target="_parent">Plugin Clients</a></li>
    </ul>
  </div>
</div>
