<?php
chdir(__DIR__);

define("PROJECT_PATH", __DIR__);

require_once(PROJECT_PATH.'/includes/initialize.php');

// ## Get JSON from post request
$payload = @file_get_contents("php://input");
$payload_decoded = json_decode($payload);

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo UCRM_PUBLIC_URL; ?> - Map</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://fonts.googleapis.com/css?family=Lato:300,400,400i,700' rel="stylesheet"> 

  </head>
  <body>
    <div id="aside">
      <?php if (!empty(LOGO_URL)) { ?>
        <img src="<?php echo LOGO_URL; ?>" class="logo">
      <?php } ?>
      <div id="description">
        <?php if (!empty(FORM_DESCRIPTION)) { ?>
          <div class="form-description">
            <?php echo FORM_DESCRIPTION; ?>
          </div>
        <?php } ?>
        <div class="buttons">
          <?php if (!empty(LINK_ONE)) { ?>
            <a href="<?php echo LINK_ONE; ?>" class="btn" style="background-color: #28a745;"><?php echo TEXT_ONE; ?></a>
          <?php } ?>
          <?php if (!empty(LINK_TWO)) { ?>
            <a href="<?php echo LINK_TWO; ?>" class="btn" style="background-color: #007bff;"><?php echo TEXT_TWO; ?></a>
          <?php } ?>
        </div>
      </div>
    </div>
    <input id="kmzFile" value="<?php echo KMZ_FILE; ?>" type="hidden"></input>
    <div id="map"></div>
    <script type="text/javascript">
      
      window.onload = function initialize() {
        var myOptions = {
          center: new google.maps.LatLng(58.33, -98.52),
          zoom: 11,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map(document.getElementById("map"), myOptions);
        var kmzLayer = new google.maps.KmlLayer(document.getElementById("kmzFile").value);
        kmzLayer.setMap(map);
      }
    </script>

    <script type="text/javascript" async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo API_KEY; ?>"></script>

    <style type="text/css">
      html, body, div {
        font-family: 'lato';
      }
      h3, p {
        margin: 0.5em 0;
        padding: 0.5em 0;
      }
      .logo {
        display: block;
        margin: 20px auto;
        max-width: 100%;
        height: auto;
      }
      .buttons {
        text-align: center;
        margin-top: 20px;
      }
      .btn {
        color: white;
        display: inline-block;
        padding: 0.5rem 1rem;
        text-align: center;
        text-decoration: none;
        border-radius: 5px;
        -moz-border-radius: 5px;
        -ie-border-radius: 5px;
        -webkit-border-radius: 5px;
        margin-bottom: 5px;
      }
      #aside {
        top: 0;
        right: 0;
        bottom: 50%;
        left: 0;
        position: absolute;
        display: block;
      }
      #description {
        padding: 5px 20px;
      }
      p {
        padding-left: 10px;
        padding-right: 10px;
      }
      #map {
        top: 50%;
        right: 0;
        bottom: 0;
        left: 0;
        position: absolute;
        display: block;
      }

      @media (min-width: 600px) {
        #map {
          top: 0;
          left: 35%;
        }
        #aside {
          bottom: 0;
          right: 65%;
        }
      }


    </style>
  </body>
</html>
