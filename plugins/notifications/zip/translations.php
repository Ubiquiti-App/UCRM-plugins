<?php
declare(strict_types=1);
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/bootstrap.php";

use MVQN\UCRM\Plugins\Config;
use MVQN\UCRM\Plugins\Settings;

use MVQN\Localization\Translator;

$ubntSupported = [
    "ca-ES" => "Catalan",
    "cs"    => "Czech",
    "da"    => "Danish",
    "nl"    => "Dutch",
    "en-CA" => "English (Canada)",
    "en-US" => "English (US)",
    "fr"    => "French",
    "de"    => "German",
    "hu"    => "Hungarian",
    "it"    => "Italian",
    "lv"    => "Latvian",
    "pt"    => "Portuguese",
    "pt-BR" => "Portuguese (Brazil)",
    "ru"    => "Russian",
    "sk"    => "Slovak",
    "es"    => "Spanish",
    "sv"    => "Swedish",
    "tr"    => "Turkish"
];

$shares = array_keys($ubntSupported);

Translator::share($shares);