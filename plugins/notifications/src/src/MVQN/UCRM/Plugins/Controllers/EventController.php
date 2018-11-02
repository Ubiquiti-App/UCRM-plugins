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
     * @return EmailActionResult
     */
    public abstract function action(string $action, int $entityId): EmailActionResult;


    protected function replaceVariables(string $string, array $replacements = [], string $delimiter = "%"): string
    {
        $replacer = function(string $variable) use ($replacements)
        {
            return array_key_exists($variable, $replacements) ? $replacements[$variable] : $variable;
        };

        return $this->replaceVariablesFunc($string, $replacer, $delimiter);
    }


    protected function replaceVariablesFunc(string $string, callable $replacer = null, string $delimiter = "%"): string
    {
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
                $replaced = str_replace("$delimiter$match$delimiter", $replacer($match), $replaced);

            // Return the replaced string.
            return $replaced;
        }
        else
        {
            // OTHERWISE, return the original string, as not variables were found!
            return $string;
        }
    }

}