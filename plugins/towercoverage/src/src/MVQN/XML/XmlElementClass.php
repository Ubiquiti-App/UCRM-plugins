<?php
declare(strict_types=1);

namespace MVQN\XML;

use MVQN\Annotations\AnnotationReader;
use MVQN\Dynamics\AutoObject;

/**
 * Class XmlElementClass
 *
 * @package MVQN\XML
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
abstract class XmlElementClass extends AutoObject
{

    protected function cdata2mime(string $cdata): string
    {
        $data = trim($cdata);

        if($data === null || $data === "")
            return "";

        $hex = bin2hex(base64_decode($data));

        $types =
        [
            "FFD8"              =>  "jpeg",
            "89504E470D0A1A0A"  =>  "png",
            "474946"            =>  "gif",
            "424D"              =>  "bmp",
            "4949"              =>  "tiff",
            "4D4D"              =>  "tiff",

            // TODO: Add more types as needed!
        ];

        foreach($types as $signature => $type)
            if(strpos($hex, strtolower($signature)) === 0)
                return "data:image/$type;base64, $data";

        return $cdata; // Untrimmed in this case?
    }


    public function __construct(array $elements)
    {
        $annotationReader = new AnnotationReader(get_class($this));

        $xmlElementNames = [];
        $xmlElementTypes = [];
        $xmlElementOther = [];

        foreach($annotationReader->getPropertyAnnotations() as $property => $annotations)
        {
            $key = array_key_exists("XmlElement", $annotations) ? $annotations["XmlElement"] : $property;

            $xmlElementNames[$key] = $property;
            $xmlElementTypes[$key] = $annotations["var"]["types"];
            $xmlElementOther[$key] = $annotations;
        }

        foreach($elements as $key => $value)
        {
            if(!array_key_exists($key, $xmlElementNames))
                continue;

            $property = $xmlElementNames[$key];

            $types = $xmlElementTypes[$key];

            $trim = array_key_exists("Trim", $xmlElementOther[$key]);

            if($trim)
                $value = trim($value);

            $mime = array_key_exists("MIME", $xmlElementOther[$key]);

            if($mime)
                $value = $this->cdata2mime($value);

            if(count($types) === 1 || (count($types) === 2 && $types[1] === "null"))
            {
                switch ($types[0])
                {
                    case "bool":    $this->$property = (bool)$value;    break;
                    case "int":     $this->$property = (int)$value;     break;
                    case "float":   $this->$property = (float)$value;   break;
                    case "string":  $this->$property = $value;          break;
                    case "array":   $this->$property = $value;          break;
                    default:
                        $type = $types[0];
                        $isArray = false;

                        if(strpos($type, "[]") !== false)
                        {
                            $type = trim(str_replace("[]", "", $type));
                            $isArray = true;
                        }

                        $class = $annotationReader->findAnnotationClass($type);

                        if(is_subclass_of($class, XmlElementClass::class, true))
                        {
                            if($isArray)
                            {
                                $this->$property = [];

                                foreach($value as $element)
                                    $this->$property[] = new $class($element);
                            }
                            else
                            {
                                $this->$property = new $class($value);
                            }
                        }
                        break;
                }
            }
            else
            {
                // ?
                echo "";
            }





            echo "";
        }

    }


}