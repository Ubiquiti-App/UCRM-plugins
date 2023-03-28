<?php
chdir(__DIR__);

define('PROJECT_PATH', __DIR__);

require_once(PROJECT_PATH . '/includes/initialize.php');

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars(UCRM_PUBLIC_URL, ENT_QUOTES); ?> - Map</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://fonts.googleapis.com/css?family=Lato:300,400,400i,700' rel="stylesheet"> 

  </head>
  <body>
    <div id="aside">
      <?php if (! empty(\KMZMap\Config::$LOGO_URL)) { ?>
        <img src="<?php echo htmlspecialchars(\KMZMap\Config::$LOGO_URL, ENT_QUOTES); ?>" class="logo">
      <?php } ?>
      <div id="description">
        <?php if (! empty(\KMZMap\Config::$FORM_DESCRIPTION)) { ?>
          <div class="form-description">
            <?php echo nl2br(htmlspecialchars(\KMZMap\Config::$FORM_DESCRIPTION, ENT_QUOTES)); ?>
          </div>
        <?php } ?>
        <div class="buttons">
          <?php if (! empty(\KMZMap\Config::$LINK_ONE[0])) { ?>
            <a href="<?php echo htmlspecialchars(\KMZMap\Config::$LINK_ONE[0], ENT_QUOTES); ?>" class="btn" style="background-color: #28a745;"><?php echo htmlspecialchars(\KMZMap\Config::$LINK_ONE[1], ENT_QUOTES); ?></a>
          <?php } ?>
          <?php if (! empty(\KMZMap\Config::$LINK_TWO[0])) { ?>
            <a href="<?php echo htmlspecialchars(\KMZMap\Config::$LINK_TWO[0], ENT_QUOTES); ?>" class="btn" style="background-color: #007bff;"><?php echo htmlspecialchars(\KMZMap\Config::$LINK_TWO[1], ENT_QUOTES); ?></a>
          <?php } ?>
        </div>
      </div>
    </div>
    <input id="kmzFile" value="<?php echo htmlspecialchars(\KMZMap\Config::$KMZ_FILE, ENT_QUOTES); ?>" type="hidden"></input>
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

    <script type="text/javascript" async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(\KMZMap\Config::$GOOGLE_API_KEY, ENT_QUOTES); ?>"></script>

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
