<?php

namespace KMZMap;

class Config
{
    public static $GOOGLE_API_KEY = null;

    public static $KMZ_FILE = null;

    public static $LOGO_URL = null;

    public static $FORM_DESCRIPTION = null;

    public static $LINK_ONE = null;

    public static $LINK_TWO = null;

    public static function initializeStaticProperties($config_path)
    {

    // ## Setup user configuration settings, if they exist
        if (file_exists($config_path)) {
            // ## Get file and decode
            $config_string = file_get_contents($config_path);
            $config_json = json_decode($config_string);

            foreach ($config_json as $key => $value) {

        // ## Expect specific key naming convention
                $name = false;
                $count = false;
                if (strpos($key, 'REQUIRED_') !== false) {
                    $name = str_replace('REQUIRED_', '', $key);
                    $new_value = $value;
                } elseif (strpos($key, 'HYPER_LINK_') !== false) {
                    $name = str_replace('HYPER_LINK_', '', $key);
                    $new_value = self::parseLink($value);
                } elseif (strpos($key, 'OPTIONAL_') !== false) {
                    $name = str_replace('OPTIONAL_', '', $key);
                    $new_value = $value;
                }

                // ## Do not define if no name is set
                if ($name !== false) {
                    // ## Set to null if value is empty
                    if (! empty($new_value)) {
                        self::${$name} = $new_value;
                    } else {
                        self::${$name} = null;
                    }
                }
            }
        }
    }

    private static function parseLink($link_string)
    {
        return explode('|', $link_string, 2);
    }
}
