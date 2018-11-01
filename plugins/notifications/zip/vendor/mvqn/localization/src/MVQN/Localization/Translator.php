<?php
declare(strict_types=1);

namespace MVQN\Localization;

use MVQN\Localization\Exceptions\DictionaryException;
use Stichoza\GoogleTranslate\TranslateClient;

use MVQN\Localization\Exceptions\TranslatorException;

final class Translator
{
    /** @const int The default JSON encoding options used when serializing to translation files. */
    private const DEFAULT_JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    /** @var string The locale to use for translation keys. */
    private const TEACHING_LOCALE = Locale::ENGLISH_UNITED_STATES;

    // =================================================================================================================
    // PROPERTIES
    // =================================================================================================================

    /** @var string The directory in which the dictionary files should be stored. */
    private static $dictionaryDirectory;

    /** @var array An array of currently supported locales. */
    private static $supportedLocales;

    /** @var string The currently configured locale. */
    private static $currentLocale;




    private static $translationClient;

    // =================================================================================================================
    // PATHS
    // =================================================================================================================

    /**
     * Gets the absolute path to the directory in which the dictionary should be stored.
     *
     * @return string Returns the directory to the dictionary files, or throws an Exception if none has been set.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function getDictionaryDirectory(): string
    {
        // IF the dictionary directory has NOT already been set, THEN throw an Exception!
        if(self::$dictionaryDirectory === null || self::$dictionaryDirectory === "")
            throw new TranslatorException("No dictionary directory has been set, use ".
                "Translator::setDictionaryDirectory() before calling any Translator methods!\n");

        // IF the dictionary directory does NOT exist, THEN throw an Exception!
        if(realpath(self::$dictionaryDirectory) === false)
            throw new DictionaryException("The dictionary directory '".self::$dictionaryDirectory."' is missing!".
                "Translator::setDictionaryDirectory() will automatically create the necessary directory!\n");

        // OTHERWISE, return the absolute path to the dictionary directory!
        return self::$dictionaryDirectory;
    }

    /**
     * Sets the absolute path to the directory in which the dictionary should be stored.
     *
     * @param string $directory The directory in which to translation files should be stored.
     * @throws TranslatorException
     */
    public static function setDictionaryDirectory(string $directory): void
    {
        // IF the provided path is NOT a directory, THEN throw an Exception!
        if(!is_dir($directory))
            throw new TranslatorException("The provided path '$directory' is not a directory and is therefore not a ".
                "valid parameter for Translator::setDictionaryDirectory!\n");

        // IF the provided path was a directory, but it does NOT exist, THEN create it, recursively, as needed!
        if(!file_exists($directory))
            mkdir($directory, 0775, true);

        // IF the provided path still does NOT exist, THEN throw an Exception!
        if(realpath($directory) === false)
            throw new TranslatorException("The dictionary directory '$directory' could not be created!\n");

        // OTHERWISE, set and return the absolute path to the dictionary directory!
        self::$dictionaryDirectory = realpath($directory);
    }

    // =================================================================================================================
    // LOCALES
    // =================================================================================================================

    /**
     * Gets the currently supported locales.
     *
     * @return array Returns an array of currently supported locales.
     * @throws \ReflectionException
     */
    public static function getSupportedLocales(): array
    {
        // IF the supported locales have NOT already been determined...
        if(self::$supportedLocales === null || self::$supportedLocales === [])
        {
            // THEN use Reflection to get a complete list of all constants from the Locale class.
            $reflection = new \ReflectionClass(Locale::class);

            // And set the supported locales.
            self::$supportedLocales = $reflection->getConstants();
        }

        // Finally, return the supported locales!
        return self::$supportedLocales;
    }

    /**
     * Checks to see if the specified locale is currently supported.
     *
     * @param string $locale The locale for which to check for current support.
     * @return bool Returns TRUE if the locale is currently supported, otherwise FALSE.
     * @throws \ReflectionException
     */
    public static function isSupportedLocale(string $locale): bool
    {
        return (array_search($locale, array_values(self::getSupportedLocales()), true) !== false);
    }

    /**
     * Gets the teaching locale.
     *
     * @return string Returns the teaching locale.
     */
    public static function getTeachingLocale(): string
    {
        return self::TEACHING_LOCALE;
    }

    /**
     * Checks to see if the specified locale is the sames as the teaching locale.
     *
     * @param string $locale The locale for which to check.
     * @return bool Returns TRUE if the locale is the same as the teaching locale, otherwise FALSE.
     */
    public static function isTeachingLocale(string $locale): bool
    {
        return ($locale === self::TEACHING_LOCALE);
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Gets the current locale.
     *
     * @return string Returns the current locale, or throws an Exception if no locale has been set.
     * @throws TranslatorException
     */
    public static function getCurrentLocale(): string
    {
        // IF the current locale is not already set, THEN throw an Exception!
        if(self::$currentLocale === null || self::$currentLocale === "")
            throw new TranslatorException("No locale has been set, use Translator::setCurrentLocale() before calling ".
                "any Translator methods!\n");

        // OTHERWISE, return the locale.
        return self::$currentLocale;
    }

    /**
     * Sets the current locale.
     *
     * @param string $locale The locale to set as current.
     * @param bool $force If TRUE, then the locale is set regardless of being currently supported, defaults to FALSE.
     * @throws TranslatorException
     * @throws \ReflectionException
     */
    public static function setCurrentLocale(string $locale, bool $force = false): void
    {
        // IF the specified locale is NOT valid, THEN throw an Exception!
        if($locale === null || $locale === "")
            throw new TranslatorException("An invalid locale was provided '$locale'!\n");

        // IF the locale is NOT supported and force is NOT TRUE, THEN throw an Exception!
        if(!$force && !self::isSupportedLocale($locale))
            throw new TranslatorException("The locale '$locale' is not currently supported!\n");

        // OTHERWISE, set the current locale.
        self::$currentLocale = $locale;
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Gets all of the currently translated locales.
     *
     * @return array Returns an array of all of the currently translated locales.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function getTranslatedLocales(): array
    {
        // Initialize an empty collection to store the translated locales.
        $translatedLocales = [];

        // Get the absolute path to the directory where the dictionary files reside.
        $directory = self::getDictionaryDirectory();

        // Loop through each file in the directory...
        foreach(scandir($directory) as $file)
        {
            // IF the file is special OR the file is the teaching locale, THEN skip!
            if($file === "." || $file === ".." || $file === self::TEACHING_LOCALE.".json")
                continue;

            // OTHERWISE, return the locale, given the file name.
            $translatedLocales[] = str_replace(".json", "", $file);
        }

        // Return the collection of translated locales.
        return $translatedLocales;
    }

    // =================================================================================================================
    // FILES
    // =================================================================================================================

    /**
     * Gets the dictionary filename associated with the provided (or current) locale and creates the file as needed.
     *
     * @param string $locale An optional locale for which to get the translation file, defaults to the current locale.
     * @return string Returns the absolute path to the translation file.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function getDictionaryFilename(string $locale = ""): string
    {
        // IF no locale has been specified, THEN set the current locale.
        if($locale === "")
            $locale = self::getCurrentLocale();

        // Get the absolute path to the directory where the dictionary files reside.
        $directory = self::getDictionaryDirectory();

        // Generate the dictionary filename.
        $dictionaryFilename = "$directory/$locale.json";

        // IF the file does NOT already exist, THEN create an empty dictionary file!
        if(!file_exists($dictionaryFilename))
            file_put_contents($dictionaryFilename, "{}");

        // IF the dictionary file still does NOT exist, THEN throw an Exception!
        if(realpath($dictionaryFilename) === false)
            throw new TranslatorException("The locale file '$dictionaryFilename' could not be created!\n");

        // OTHERWISE, return the absolute path to the dictionary file!
        return realpath($dictionaryFilename);
    }

    /**
     * Loads a dictionary file for the provided (or current) locale.
     *
     * @param string $locale An optional locale for which to get the translation file, defaults to the current locale.
     * @return array Returns the dictionary as an associative array.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function loadDictionary(string $locale = ""): array
    {
        // IF no locale has been specified, THEN set the current locale.
        if($locale === "")
            $locale = self::getCurrentLocale();

        // Generate the dictionary filename.
        $dictionaryFilename = self::getDictionaryFilename($locale);

        // Read the dictionary file, decode it and convert it into an associative array.
        $dictionary = json_decode(file_get_contents($dictionaryFilename), true) ?: [];

        // Return the dictionary!
        return $dictionary;
    }

    /**
     * Saves translations to the translation file for the provided (or current) locale.
     *
     * @param array $dictionary
     * @param string $locale
     * @return array
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function saveDictionary(array $dictionary, string $locale = ""): array
    {
        // IF no locale has been specified, THEN set the current locale.
        if($locale === "")
            $locale = self::getCurrentLocale();

        // Sort the dictionary.
        ksort($dictionary);

        // Generate the dictionary filename.
        $dictionaryFilename = self::getDictionaryFilename($locale);

        // Encode the dictionary and then write it to a dictionary file.
        file_put_contents($dictionaryFilename, json_encode($dictionary, self::DEFAULT_JSON_OPTIONS));

        return $dictionary;
    }

    // =================================================================================================================
    // INQUIRIES
    // =================================================================================================================

    /**
     * Checks to see if the specified (or current) locale has a known translation for a word or phrase.
     *
     * @param string $text The word or phrase for which to check for a translation.
     * @param string $locale An optional locale to check, defaults to the current locale.
     * @return bool Returns TRUE if a translation exists, otherwise FALSE.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function knows(string $text, string $locale = ""): bool
    {
        // IF no locale has been specified, THEN set the current locale.
        if($locale === "")
            $locale = self::getCurrentLocale();

        // Load the dictionary for the specified locale.
        $dictionary = self::loadDictionary($locale);

        // Return TRUE, if the dictionary has the word or phrase as a key!
        return (array_key_exists($text, $dictionary) && $dictionary[$text] !== null && $dictionary[$text] !== "");
    }

    /**
     * Checks to see if the provided (or current) locale has a known translation and then returns the translation.
     * NOTE: Translator::ask() does not do any online checks and only returns locally stored translations.
     *
     * @param string $text The text for which to check a matching translation.
     * @param string $locale An optional locale to check, defaults to the current locale.
     * @return string Returns the translated text, if it exists, or an empty string.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function ask(string $text, string $locale = ""): string
    {
        // IF no locale has been specified, THEN set the current locale.
        if($locale === "")
            $locale = self::getCurrentLocale();

        // IF there are no known translations for the word or phrase, THEN return an empty string!
        if(!self::knows($text, $locale))
            return "";

        // OTHERWISE, load the corresponding dictionary.
        $dictionary = self::loadDictionary($locale);

        // Return the translation for the word or phrase!
        return $dictionary[$text];
    }

    // =================================================================================================================
    // LEARNING
    // =================================================================================================================

    /**
     * Checks for a translation of the given text in the provided (or current) locale and then saves it locally.
     * NOTE: Translator::learn() will attempt an online translation of the given text if a local one does not exist.
     *
     * @param string $text The text for which to check a matching translation.
     * @param string $locale An optional locale to check, defaults to the current locale.
     * @return string Returns the translated text, if it exists or can be determined online, or an empty string.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function learn(string $text, string $locale = ""): string
    {
        // IF no locale has been specified, THEN set the current locale.
        if($locale === "")
            $locale = self::getCurrentLocale();

        // Load the dictionaries for the specified locale and the teaching locale.
        $localeDictionary = self::loadDictionary($locale);

        // Introduce the word or phrase to the teaching dictionary.
        // NOTE: Nothing will happen if the word or phrase already exists in the teaching dictionary!
        self::introduce($text);

        // IF the word or phrase already exists in the locale dictionary, THEN simply return that and do NOT continue!
        // NOTE: This is to avoid extraneous look-ups to the Google Translation API.
        if(array_key_exists($text, $localeDictionary))
            return $localeDictionary[$text];

        // Create a variable to store the current locale, in case the locale needs to be temporarily changed.
        $oldLocale = "";

        // IF a TranslationClient has already been instantiated, THEN store the current locale...
        if(self::$translationClient)
            $oldLocale = self::getCurrentLocale();

        // (Re-)Instantiate a TranslationClient to translate between the teaching and the specified locales.
        self::$translationClient = new TranslateClient(self::TEACHING_LOCALE, $locale);

        // Translate the word or phrase.
        $translated = self::$translationClient->translate($text);

        // THEN the translation to the locale dictionary.
        $localeDictionary[$text] = $translated;

        // Save the locale dictionary.
        self::saveDictionary($localeDictionary, $locale);

        // Revert the TranslationClient to the previous locale.
        if($oldLocale !== "")
            self::$translationClient = new TranslateClient(self::TEACHING_LOCALE, $oldLocale);

        // Return the translation!
        return $translated;
    }

    public static function forget(string $text, string $locale = ""): bool
    {
        if($locale === "")
            $locale = self::getCurrentLocale();

        if(!self::knows($text, $locale))
            return false;

        $dictionary = self::loadDictionary($locale);
        unset($dictionary[$text]);
        self::saveDictionary($dictionary, $locale);

        return true;
    }

    // =================================================================================================================
    // TEACHING
    // =================================================================================================================

    /**
     * Introduces a new word or phrase to the translator.
     *
     * @param string $text The word or phrase to be introduced.
     * @return bool Returns TRUE if the word or phrase was not previously known, otherwise FALSE.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function introduce(string $text): bool
    {
        // Load the current teaching dictionary.
        $teachingDictionary = self::loadDictionary(self::TEACHING_LOCALE);

        // IF the word or phrase already exists, THEN simply return FALSE!
        if(array_key_exists($text, $teachingDictionary))
            return false;

        // OTHERWISE, add the word or phrase to the teaching dictionary.
        $teachingDictionary[$text] = $text;

        // And the save the revised teaching dictionary.
        self::saveDictionary($teachingDictionary, self::TEACHING_LOCALE);

        // Finally, return TRUE!
        return true;
    }



    public static function withdraw(string $text): bool
    {
        $locale = self::TEACHING_LOCALE;

        if(!self::knows($text, $locale))
            return false;

        $dictionary = self::loadDictionary($locale);
        unset($dictionary[$text]);
        self::saveDictionary($dictionary, $locale);

        return true;
    }

    public static function isFamiliar(string $text): bool
    {
        return Translator::knows($text, self::TEACHING_LOCALE);
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Teach a new word or phrase to the translator, given the provided (or current) locale.
     *
     * @param string $text The word or phrase to be taught.
     * @param string $translation The translated equivalent of the word or phrase.
     * @param string $locale The locale of which this word or phrase belongs, defaults to the current locale.
     * @return bool Returns TRUE if the word or phrase was not previously known, otherwise FALSE.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function teach(string $text, string $translation, string $locale = ""): bool
    {
        // IF no locale was provided, THEN set it to the current locale!
        if($locale === "")
            $locale = self::getCurrentLocale();

        //$localeFile = self::getDictionaryFilename($locale);
        $localeDictionary = self::loadDictionary($locale);

        if(array_key_exists($text, $localeDictionary) && $localeDictionary[$text] === $translation)
            return false;

        $localeDictionary[$text] = $translation;
        //file_put_contents($localeFile, json_encode($localeDictionary, self::DEFAULT_JSON_OPTIONS));
        self::saveDictionary($localeDictionary, $locale);

        //$directory = self::getDictionaryDirectory();

        $teaching = self::loadDictionary(self::TEACHING_LOCALE);

        if(!array_key_exists($text, $teaching))
        {
            $teaching[$text] = $text;
            self::saveDictionary($teaching, self::TEACHING_LOCALE);
        }

        return true;
    }

    /**
     * Shares all known words and phrases from the teaching locale with the specified locales.
     *
     * @param array $locales An optional array of locales for which to share with, or all locales if none specified.
     * @return array Returns an array of the affected locales.
     * @throws DictionaryException
     * @throws TranslatorException
     */
    public static function share(array $locales = []): array
    {
        $sharedTo = [];

        //$directory = self::getDictionaryDirectory();

        if($locales === [])
            $locales = self::getTranslatedLocales();

        $teaching = self::loadDictionary(self::TEACHING_LOCALE);

        foreach($locales as $locale)
        {
            if($locale === self::TEACHING_LOCALE)
                continue;

            //echo "Starting Translator::share() on '$locale'...";

            $currentTranslations = self::loadDictionary($locale);

            $updated = false;

            foreach($teaching as $key => $value)
            {
                if(!array_key_exists($key, $currentTranslations))
                {
                    $currentTranslations[$key] = self::learn($key, $locale); //, false);
                    $updated = true;
                    //echo ".";
                }
            }

            self::saveDictionary($currentTranslations, $locale);

            if($updated)
                $sharedTo[] = $locale;

            //echo "DONE!\n";
        }

        return $sharedTo;
    }










    public static function getTwigFilterTranslate(): \Twig_Filter
    {
        return new \Twig_Filter("translate",
            function(string $text, string $locale = "")
            {
                if ($locale === "")
                    $locale = Translator::getCurrentLocale();

                if(Translator::knows($text, $locale))
                    return Translator::ask($text, $locale);

                Translator::introduce($text);
                Translator::share([ $locale ]);

                return Translator::ask($text, $locale);
            }
        );
    }




}