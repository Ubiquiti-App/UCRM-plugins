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
    /**
     * Converts an XML CDATA element to it's respective MIME type.
     *
     * @param string $cdata The CDATA for which to convert.
     * @return string Returns an HTML string ready for usage as a MIME object.
     */
    protected function cdata2mime(string $cdata): string
    {
        // Trim any white-space from the CDATA string.
        $data = trim($cdata);

        // IF the data is empty, THEN return an empty string!
        if($data === null || $data === "")
            return "";

        // OTHERWISE, convert the CDATA string to a binary hex string for header comparison of MIME types.
        $hex = bin2hex(base64_decode($data));

        // Mapping of binary hex string signatures to their MIME types.
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

        // Loop through each mapping to determine the correct MIME type...
        foreach($types as $signature => $type)
        {
            // IF the CDATA begins with a known signature, THEN return the converted string!
            if (strpos($hex, strtolower($signature)) === 0)
                return "data:image/$type;base64, $data";
        }

        // No matching signature was found, so simply return the original CDATA string!
        return $cdata; // Should we return the trimmed version?
    }

    /**
     * XmlElementClass constructor.
     *
     * @param array $elements An associative array of XML elements
     * @throws \ReflectionException
     */
    public function __construct(array $elements)
    {
        // Create an annotation reader for the class.
        $annotationReader = new AnnotationReader(get_class($this));

        $xmlElementNames = [];
        $xmlElementTypes = [];
        $xmlElementOther = [];

        // Loop through each class property, pairing any @XmlElement annotations to the property name, type and other...
        foreach($annotationReader->getPropertyAnnotations() as $property => $annotations)
        {
            $key = array_key_exists("XmlElement", $annotations) ? $annotations["XmlElement"] : $property;

            $xmlElementNames[$key] = $property;
            $xmlElementTypes[$key] = $annotations["var"]["types"];
            $xmlElementOther[$key] = $annotations;
        }

        // Loop through each node in the provided data...
        foreach($elements as $key => $value)
        {
            // IF the node does not have a mapping to a property, THEN skip this node!
            if(!array_key_exists($key, $xmlElementNames))
                continue;

            // Get the property name and type from the node mappings.
            $property = $xmlElementNames[$key];
            $types = $xmlElementTypes[$key];

            // IF there is a @Trim annotation, THEN trim the value prior to parsing.
            if(array_key_exists("Trim", $xmlElementOther[$key]))
                $value = trim($value);

            // IF there is a @MIME annotation, THEN convert the value prior to parsing.
            if(array_key_exists("MIME", $xmlElementOther[$key]))
                $value = $this->cdata2mime($value);

            // IF exactly one property type exists OR there are two types, but the second is null...
            if(count($types) === 1 || (count($types) === 2 && $types[1] === "null"))
            {
                // THEN handle each type differently...
                switch ($types[0])
                {
                    // Scalar types are easy!
                    case "bool":    $this->$property = (bool)$value;    break;
                    case "int":     $this->$property = (int)$value;     break;
                    case "float":   $this->$property = (float)$value;   break;
                    case "string":  $this->$property = $value;          break;

                    // Arrays are pretty straight-forward as well!
                    case "array":   $this->$property = $value;          break;

                    // Other (inherited) types require a little work...
                    default:
                        $type = $types[0];
                        $isArray = false;

                        // Determine if the type is annotated as an array...
                        if(strpos($type, "[]") !== false)
                        {
                            $type = trim(str_replace("[]", "", $type));
                            $isArray = true;
                        }

                        // Get an annotation reader for the annotated class.
                        $class = $annotationReader->findAnnotationClass($type);

                        // IF the class extends XmlElement...
                        if(is_subclass_of($class, XmlElementClass::class, true))
                        {
                            // AND the type was an array of this class...
                            if($isArray)
                            {
                                // THEN handle this node as a collection.
                                $this->$property = [];

                                // Loop through each element and parse using this same constructor, recursively...
                                foreach($value as $element)
                                    $this->$property[] = new $class($element);
                            }
                            else
                            {
                                // OTHERWISE, simply parse the element using this same constructor.
                                $this->$property = new $class($value);
                            }
                        }
                        break;

                        // TODO: Better checking for missed types!
                }
            }
            else
            {
                // A currently unsupported syntax was used in the property annotation, use:
                // @var <type>[|null] [<description>]
                echo "";
            }

            // Everything should be parsed at this point!!!
        }
    }
}
