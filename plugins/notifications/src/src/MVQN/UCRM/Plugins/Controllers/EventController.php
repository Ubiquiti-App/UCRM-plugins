<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Controllers;

/**
 * Class EventController
 *
 * @package MVQN\UCRM\Plugins\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
abstract class EventController
{
    /** @var \Twig_Environment */
    protected $twig;

    /**
     * EventController constructor.
     *
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param string $action
     * @param int $entityId
     * @return EmailActionResult[]
     */
    public abstract function action(string $action, int $entityId): array;


    public static function replaceVariables(string $string, array $replacements = [],
        array &$statics = null, array &$dynamics = null, string $delimiter = "%"): string
    {
        $replacer = function(string $variable) use ($replacements, &$statics, &$dynamics, $delimiter)
        {
            return array_key_exists($variable, $replacements) ? $replacements[$variable] : "";
        };

        return self::replaceVariablesFunc($string, $replacer, $statics, $dynamics, $delimiter);
    }


    public static function replaceVariablesFunc(string $string, callable $replacer = null,
        array &$statics = null, array &$dynamics = null, string $delimiter = "%"): string
    {
        $statics = [];
        $dynamics = [];

        // IF the variables string is empty or NULL...
        if($string === "" && $string === null)
            return "";

        // Duplicate the string to allow for changes.
        $replaced = $string;

        // Design the pattern for matching, based on the delimiter provided.
        $pattern = "/$delimiter(\w+)$delimiter/m";

        // IF and matches exist...
        if(preg_match_all($pattern, $string, $matches))
        {
            // THEN loop through the matches...
            foreach($matches[1] as $match)
                $replaced = str_replace("$match", $replacer($match), $replaced);

            $parts = array_map(
                function(string $current) use (&$statics, &$dynamics, $delimiter)
                {
                    if(strpos($current, $delimiter) !== false)
                    {
                        if(substr($current, 0, 1) !== $delimiter || substr($current, -1, 1) !== $delimiter)
                            throw new \Exception("Parsing Error!");

                        $dynamics[] = trim(str_replace($delimiter, "", $current));
                    }
                    else
                    {
                        $statics[] = $current;
                    }
                },
                array_map("trim", explode(",", $replaced))
            );

            // Return the replaced string.
            return str_replace($delimiter, "", $replaced);
        }
        else
        {
            $statics = array_map("trim", explode(",", $string));

            // OTHERWISE, return the original string, as not variables were found!
            return $string;
        }
    }

}