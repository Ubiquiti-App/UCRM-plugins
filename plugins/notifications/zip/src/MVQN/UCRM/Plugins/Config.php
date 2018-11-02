<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins;

use MVQN\Dynamics\AutoObject;

use MVQN\Data\Database;
use MVQN\UCRM\Data\Models\Option;

/**
 * Class Config
 * @package MVQN\UCRM\Plugins
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 *
 * @method static string|null getLanguage()
 * @method static string|null getSmtpTransport()
 * @method static string|null getSmtpUsername()
 * @method static string|null getSmtpPassword()
 * @method static string|null getSmtpHost()
 * @method static string|null getSmtpPort()
 * @method static string|null getSmtpEncryption()
 * @method static string|null getSmtpAuthentication()
 * @method static bool|null   getSmtpVerifySslCertificate()
 * @method static string|null getSmtpSenderEmail()
 * @method static string|null getGoogleApiKey()
 */
final class Config extends AutoObject
{
    /** @var string */
    protected static $language;

    /** @var string */
    protected static $smtpTransport;

    /** @var string */
    protected static $smtpUsername;

    /** @var string */
    protected static $smtpPassword;

    /** @var string */
    protected static $smtpHost;

    /** @var string */
    protected static $smtpPort;

    /** @var string */
    protected static $smtpEncryption;

    /** @var string */
    protected static $smtpAuthentication;

    /** @var bool */
    protected static $smtpVerifySslCertificate;

    /** @var string */
    protected static $smtpSenderEmail;

    /** @var string */
    protected static $googleApiKey;

    /**
     * Executes prior to the very fist static __call() method and used to initialize the properties of this class.
     *
     * @throws Exceptions\ManifestElementException
     * @throws Exceptions\PluginNotInitializedException
     * @throws Exceptions\RequiredDirectoryNotFoundException
     * @throws Exceptions\RequiredFileNotFoundException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \MVQN\Data\Exceptions\DatabaseConnectionException
     * @throws \MVQN\Data\Exceptions\ModelClassException
     * @throws \ReflectionException
     */
    public static function __beforeFirstStaticCall(): bool
    {
        Log::info("*** CONFIGURATION: START");

        // =============================================================================================================
        // ENVIRONMENT
        // =============================================================================================================

        // Get the path to an optional .env file for development.
        $envPath = realpath(__DIR__ . "/../../../../../");

        // IF an .env file exists, THEN initialize environment variables from the .env file!
        if (file_exists($envPath . "/.env"))
            (new \Dotenv\Dotenv($envPath))->load();

        // =============================================================================================================
        // SETTINGS
        // =============================================================================================================

        // Initialize the Plugin libraries using the current directory as the root.
        Plugin::initialize(__DIR__ . "/../../../../");

        // Regenerate the Settings class, in case anything has changed in the manifest.json file.
        Plugin::createSettings();

        // Create a database connection using environment variables, from either an .env file or the actual environment.
        // NOTE: These variables all exist on the production servers!
        Database::connect(
            getenv("POSTGRES_HOST"),
            (int)getenv("POSTGRES_PORT"),
            getenv("POSTGRES_DB"),
            getenv("POSTGRES_USER"),
            getenv("POSTGRES_PASSWORD")
        );

        // Generate the Cryptographic Key used by the Crypto library from the has already created by the UCRM server.
        // NOTE: The '../../encryption/crypto.key' file will not exist in development environments and the crypto hash
        // will need to be included in an .env file for decryption to work in development!
        $cryptoKey = Plugin::getCryptoKey() ?? \Defuse\Crypto\Key::loadFromAsciiSafeString(getenv("CRYPTO_KEY"));

        // Get a collection of all rows of the option table from the database!
        $options = Option::select();

        /** @var Option $option */

        // LANGUAGE/LOCALE
        $option = $options->where("code", "APP_LOCALE")->first();
        self::$language = $option->getValue();
        Log::info("Locale was determined as '" . self::$language . "' by UCRM Settings.");

        // SMTP TRANSPORT
        $option = $options->where("code", "MAILER_TRANSPORT")->first();
        self::$smtpTransport = $option->getValue();
        Log::info("Transport Type determined as '" . self::$smtpTransport . "' by UCRM Settings.");

        //if (self::$smtpTransport !== "smtp")
        //    Log::error("Only SMTP transport is currently supported!", \Exception::class);

        // SMTP USERNAME
        $option = $options->where("code", "MAILER_USERNAME")->first();
        self::$smtpUsername = $option->getValue();
        Log::info("SMTP Username determined as '" . self::$smtpUsername . "' by UCRM Settings.");

        // SMTP PASSWORD
        $option = $options->where("code", "MAILER_PASSWORD")->first();
        self::$smtpPassword = $option->getValue() !== "" ? Plugin::decrypt($option->getValue(), $cryptoKey) : null;
        //Log::info("SMTP Password determined as '$smtpPassword' by UCRM Settings.");

        if (self::$smtpPassword !== null && self::$smtpPassword !== "")
            Log::info("SMTP Password successfully decrypted from UCRM Settings.");
        else
            Log::error("SMTP Password could not be determined by UCRM Settings!", \Exception::class);

        // SMTP HOST
        $option = $options->where("code", "MAILER_HOST")->first();
        self::$smtpHost = self::$smtpTransport === "gmail" ? "smtp.gmail.com" : $option->getValue();
        Log::info("SMTP Server determined as '" . self::$smtpHost . "' by UCRM Settings.");

        // SMTP PORT
        $option = $options->where("code", "MAILER_PORT")->first();
        self::$smtpPort = self::$smtpTransport === "gmail" ? "587" : $option->getValue();
        Log::info("SMTP Port determined as '" . self::$smtpPort . "' by UCRM Settings.");

        // SMTP ENCRYPTION
        $option = $options->where("code", "MAILER_ENCRYPTION")->first();
        // None = "", SSL = "ssl", TLS = "tls"
        self::$smtpEncryption = self::$smtpTransport === "gmail" ? "tls" : $option->getValue();
        Log::info("SMTP Encryption determined as '" . (self::$smtpEncryption !== "" ? self::$smtpEncryption : "none") .
            "' by UCRM Settings.");

        // SMTP AUTHENTICATION ( None = "", Plain = "plain", Login = "login", CRAM-MD5 = "cram-md5" )
        $option = $options->where("code", "MAILER_AUTH_MODE")->first();
        self::$smtpAuthentication = self::$smtpTransport === "gmail" ? "login" : $option->getValue(); //
        Log::info("SMTP Authentication determined as '" . (self::$smtpAuthentication !== "" ? self::$smtpAuthentication
            : "none") . "' by UCRM Settings.");

        // SMTP VERIFY SSL CERTIFICATE?
        $option = $options->where("code", "MAILER_VERIFY_SSL_CERTIFICATES")->first();
        self::$smtpVerifySslCertificate = (bool)$option->getValue();
        Log::info("Verify SSL Certificate determined as '" . (self::$smtpVerifySslCertificate === 0 ? "no" : "yes") .
            "' by UCRM Settings.");

        // SMTP SENDER ADDRESS
        $option = $options->where("code", "MAILER_SENDER_ADDRESS")->first();
        self::$smtpSenderEmail = $option->getValue();
        Log::info("SMTP Sender Email determined as '" . self::$smtpSenderEmail . "' by UCRM Settings.");

        // GOOGLE API KEY
        $option = $options->where("code", "GOOGLE_API_KEY")->first();
        self::$googleApiKey = $option->getValue();
        Log::info("Google API Key determined as '" . (self::$googleApiKey !== null ? self::$googleApiKey :
            "<MISSING>") . "' by UCRM Settings.");

        Log::info("*** CONFIGURATION: END");

        return true;

    }



}