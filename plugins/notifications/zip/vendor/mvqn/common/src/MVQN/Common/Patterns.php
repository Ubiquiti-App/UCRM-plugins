<?php
declare(strict_types=1);

namespace MVQN\Common;


/**
 * Class Patterns
 *
 * @package MVQN\Common
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class Patterns
{

    private const PATTERN_JSON    = "/(\{.*\})/";
    private const PATTERN_ARRAY   = "/(\[.*\])/";
    //private const PATTERN_EVAL    = "/(\`.*\`)/";



    public static function isJSON(string &$value = null, bool $mutate = true): bool
    {
        $result = preg_match(self::PATTERN_JSON, $value) == true;

        $json = null;

        if($result)
        {
            $json = json_decode($value, true);

            if($mutate && $json !== null)
                $value = $json;
        }

        return $json !== null && $result;
    }


    public static function isArray(string &$value = null, bool $mutate = true): bool
    {
        $result = preg_match(self::PATTERN_ARRAY, $value) == true;

        $array = null;

        if($result)
        {
            $array = eval("return {$value};");

            if($mutate && $array !== null)
                $value = $array;
        }

        return $array !== null && $result;
    }






    /**
     * @param string $pattern
     * @param array $params
     * @param string $token
     * @return string
     * @throws \Exception
     */
    public static function interpolateUrl(string $pattern, array $params, string $token = ":"): string
    {

        if ($pattern === null || $pattern === "")
            return "";

        if ($token === null || $token === "")
            throw new \Exception("[MVQN\Common\Patterns] A TOKEN must be provided to pattern match!");

        if (strpos($pattern, " ") !== false)
            throw new \Exception("[MVQN\Common\Patterns] The \$pattern must not contain any spaces!");

        $segments = [];

        if (strpos($pattern, $token) !== false)
        {
            $parts = explode("/", $pattern);

            //print_r($parts);

            $counter = 0;

            foreach($parts as $part)
            {
                if($part === "" && $counter !== 0)
                    throw new \Exception("[MVQN\Common\Patterns] The \$pattern must be in a valid URL format.");

                if (strpos($part, $token) !== 0)
                {
                    $segments[] = $part;
                    $counter++;
                    continue;
                }

                $part = substr($part, 1, strlen($part) - 1);

                if(!array_key_exists($part, $params))
                    throw new \Exception("[MVQN\Common\Patterns] Parameter '$part' was not found in \$params!");

                $segments[] = $params[$part];

                $counter++;
            }

            return implode("/", $segments);
        }
        else
        {
            return $pattern;
        }


    }


}
