<?php
namespace UCS;

class Config {
  public static $FORM_TITLE = null;
  public static $LOGO_URL = null;
  public static $FORM_DESCRIPTION = null;
  public static $COMPLETION_TEXT = null;
  public static $LEAD = null;
  
  private static function parseLink($link_string) {
    $link_array = explode('|', $link_string, 2);
    return $link_array;
  }

  public static function PLUGIN_URL() {
    $root_url = str_replace('/_plugins/ucrm-client-signup/public.php', '', self::$PLUGIN_PUBLIC_URL);
    return $root_url;
  }

  public static function initializeStaticProperties($config_path) {
    
    // ## Setup user configuration settings, if they exist
    if (file_exists($config_path)) {
      // ## Get file and decode
      $config_string = file_get_contents($config_path);
      $config_json = json_decode($config_string);

      \log_event('config json', print_r($config_json, true));
      
      foreach ($config_json as $key => $value) {

        // ## Expect specific key naming convention
        $name = false;
        $count = false;
        if (strpos(strtoupper($key), 'REQUIRED_') !== false) { 
        
          $name = str_replace('REQUIRED_', '', strtoupper($key));
          $new_value = $value;

        } elseif (strpos(strtoupper($key), 'HYPER_LINK_') !== false) { 

          $name = str_replace('HYPER_LINK_', '', strtoupper($key)); 
          $new_value = self::parseLink($value);
        
        } elseif (strpos(strtoupper($key), 'OPTIONAL_') !== false) { 
        
          $name = str_replace('OPTIONAL_', '', strtoupper($key)); 
          $new_value = $value;
        
        }

        // ## Do not define if no name is set
        if ($name !== false) {
          // ## Set to null if value is empty
          if (!empty($new_value)) {
            self::$$name = $new_value;
            \log_event($name, $new_value);
          } else {
            self::$$name = null;
          }
        }


      }

    }
  }

}

