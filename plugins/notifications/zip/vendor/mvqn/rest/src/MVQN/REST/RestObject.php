<?php
declare(strict_types=1);

namespace MVQN\REST;

use MVQN\Annotations\AnnotationReader;
use MVQN\Common\Arrays;
use MVQN\Dynamics\AutoObject;
use MVQN\Common\Strings;

/**
 * Class RestObject
 *
 * @package MVQN\REST
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class RestObject extends AutoObject implements \JsonSerializable
{
// =================================================================================================================
    // CONSTANTS
    // -----------------------------------------------------------------------------------------------------------------

    /** @var string The root namespace of Lookup classes. */
    //private const LOOKUP_NAMESPACE = __NAMESPACE__."\\Endpoints\\Lookups";

    /** @var string A string delimiter to use when @keepNull and @keepNullElements annotations are used. */
    private const NULL_DELIMITER = "#NULL#";

    // =================================================================================================================
    // MAGIC METHODS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        // Add each provided key as a property with the given value to this object...
        foreach($values as $key => $value)
            $this->$key = $value;
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Overrides the default string representation of the class.
     *
     * @return string Returns a JSON representation of this Model.
     */

    public function __toString()
    {
        // Return the array as a JSON string.
        return json_encode($this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }








    // =================================================================================================================
    // INTERFACE IMPLEMENTATIONS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed Data which can be serialized by <b>json_encode</b>, as a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        // Get an array of all Model properties.
        $assoc = get_object_vars($this);

        // Move ID to the first element in the array for readability.
        if(array_key_exists("id", $assoc))
            $assoc = ["id" => $assoc["id"]] + $assoc;

        // Return the array!
        return $assoc;
    }

    // =================================================================================================================
    // PREPARATION METHODS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Converts the RestObject to it's JSON representation, removing any values that should not be provided given the
     * specified HTTP method/verb and optionally removing all null fields not specifically annotated with '@keepNull' or
     * '@keepNullElements'.
     *
     * @param string $method The HTTP method/verb for which to examine each property for exclusion.
     * @param bool $filter A flag to request the resulting JSON be stripped of fields containing null values.
     * @param int $options Any optional <b>json_encode</b> options to be used.
     * @return string Returns a JSON string prepared for provision to any HTTP REST request body.
     * @throws \Exception
     */
    public function toJSON(string $method = "", bool $filter = true, int $options = 0): string
    {
        // Create a list of the builtin types.
        $types = ["int", "string", "float", "bool", "null", "array", "resource"];

        // Setup the Reflection instance for the calling class.
        $class = get_called_class();
        $reflection = new \ReflectionClass($class);

        // Get an array of all Model properties, via Reflection.
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);

        // Initialize a new collection to store only the desired fields for this JSON object.
        $fields = [];

        foreach($properties as $property)
        {
            // Get the name of this property.
            $name = $property->getName();

            // Create an AnnotationReader for this property and get all of it's annotations.
            $annotations = new AnnotationReader($class);
            $params = $annotations->getPropertyAnnotations($name);

            // Get the information for this property, specifically the type!
            $info = $annotations->getPropertyAnnotations($name)["var"];

            // ERROR if we are unable to find the 'types' part of the @var DocBlock!
            if(!array_key_exists("types", $info))
                throw new \Exception("[MVQN\Annotations\AnnotationReader] Unable to successfully parse the DocBlock for '$name', ".
                    "missing @var type!");

            // Set the 'type' from the property info collection.
            $type = $info["types"][0];

            // IF the current property has either the '@<method>' or '@<method>Required' or we want all properties...
            if (array_key_exists(ucfirst($method), $params) || array_key_exists($method, $params) ||
                array_key_exists(ucfirst($method)."Required", $params) || array_key_exists($method."-required", $params) ||
                $method === "")
            {
                // IF the $type is valid and is not in the array of built-in types...
                if($type !== null && !in_array($type, $types))
                {
                    if(Strings::contains($type, "[]"))
                        $type = str_replace("[]", "", $type);

                    // THEN determine the FQCN for the 'type' and the the 'Lookup' classes.
                    //$type = self::LOOKUP_NAMESPACE."\\$type";
                    $type = $annotations->findAnnotationClass($type);
                    //$base = self::LOOKUP_NAMESPACE."\\Lookup";
                    $base = RestObject::class;

                    // IF the current property's type is a child of 'Lookup'...
                    if(is_subclass_of($type, $base, true))
                    {
                        // THEN generate the getter name for the correct function.
                        $func = "get".ucfirst($name);

                        // AND execute the getter to retrieve the value.
                        $children = $this->$func();

                        // Check to see if the resulting value is NULL and skip this iteration if so...
                        if($children === null)
                            continue;

                        // Loop through each child class object...
                        foreach($children as $child)
                        {
                            /** @var RestObject $child */

                            // IF the current child's value is NOT an array...
                            if(!is_array($child))
                            {
                                // AND the value is NOT null...
                                if($child !== null)
                                {
                                    // THEN run this method recursively on this child object.
                                    $assoc = json_decode($child->toJSON($method), true);

                                    // IF the '@keepNullElements' annotation has been set
                                    // AND this would be an empty array...
                                    if(array_key_exists("KeepNullElements", $params) && array_filter($assoc) === [])
                                        // THEN add the NULL delimiter string as a placeholder.
                                        $fields[$name][] = self::NULL_DELIMITER;
                                    else
                                        // OTHERWISE simply add the array as the current field.
                                        $fields[$name][] = $assoc;
                                }
                                else
                                    // OTHERWISE simply add null to the current field.
                                    $fields[$name][] = null;
                            }
                            else
                            {
                                // OTHERWISE, the child is an Array and we should create a Lookup class for it!

                                // We should NEVER reach this block, in theory!
                                throw new \Exception("[MVQN\REST\RestObject] An array was found for which a Lookup class should be ".
                                    "created: ".print_r($child, true));
                            }
                        }
                    }
                    else
                    {
                        // OTHERWISE, the child is a class object that we did not expect to encounter or for which we
                        // forgot to extend the Lookup class.

                        // We should NEVER reach this block, in theory!
                        throw new \Exception("[MVQN\REST\RestObject] An object was found that does not extend from Lookup: $type");
                    }
                }
                else
                {
                    // OTHERWISE, we have a built-in type...

                    $property->setAccessible(true);
                    $value = $property->getValue($this);

                    // Simply append the property as the current field.
                    $fields[$name] = $value;
                }
            }
        }

        // If set to be filtered, do so now, which will recursively remove all keys with null values.
        $fields = $filter ? Arrays::array_filter_recursive($fields) : $fields;

        // Convert the array to JSON.
        $json = json_encode($fields, $options);

        // Now replace any occurrences of the NULL delimiter with the actual 'null' value.
        $json = str_replace("\"".self::NULL_DELIMITER."\"", "null", $json);

        // Finally, return the JSON string.
        return $json;
    }

    /**
     * Converts the RestObject to it's associative array representation, removing and values that should not be provided
     * given the specified HTTP method/verb and optionally removing all null fields not specifically annotated with
     * '@keepNull' or '@keepNullElements'.
     *
     * @param string $method The HTTP method/verb for which to examine each property for exclusion.
     * @param bool $filter A flag to request the resulting JSON be stripped of fields containing null values.
     * @return array Returns an associative array prepared for provision to any HTTP REST request body.
     * @throws \Exception
     */
    public function toArray(string $method = "", bool $filter = false): array
    {
        $json = $this->toJSON($method, $filter, 0);
        $assoc = json_decode($json, true);
        return $assoc;
    }

    // =================================================================================================================
    // VALIDATION METHODS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Checks to determine the validity of a RestObject, by comparing each properties value to NULL, when it has been
     * annotated with either '@<method>' or '@<method>Required'.
     *
     * @param string $method The HTTP method/verb for which to examine each property for validity.
     * @param array|null $missing A reference array used to store the missing/unset properties for later use.
     * @param array|null $ignored A reference array used to store any ignored properties for later use.
     * @return bool Returns TRUE if all required properties have a value set, otherwise FALSE.
     * @throws \Exception
     */
    public function validate(string $method, array &$missing = null, array &$ignored = null): bool
    {
        // Setup the Reflection instance for the calling class.
        $class = get_called_class();
        $reflection = new \ReflectionClass($class);

        // Get an array of all Model properties, via Reflection.
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);

        // Initialize a collection to store missing entries.
        $missing = [];

        // Loop through each property of the protected properties...
        foreach($properties as $property)
        {
            // Get the name of the property.
            $name = $property->getName();

            // Create an AnnotationReader to parse the annotations for this property and get the list of annotations.
            $annotations = new AnnotationReader($class);
            $params = $annotations->getPropertyAnnotations($name);

            // IF this is a required property, per the use of '@<method>Required'...
            if(array_key_exists(ucfirst($method)."Required", $params) || array_key_exists($method."-required", $params))
            {
                $conditional = $params[ucfirst($method)."Required"];
                $required = true;

                if ($conditional === "")
                {
                    $required = true;
                }
                else
                if($conditional !== "" && Strings::contains($conditional, "`"))
                {
                    $conditional = str_replace("`", "", $conditional);
                    $required = eval("use $class; return $conditional;");
                }
                else
                {
                    throw new \Exception("[MVQN\Annotations\AnnotationReader] An annotation of '@{$method}Required' needs to either have a ".
                        "value set, or a conditional statement enclosed in back-ticks (`) to be evaluated at runtime.");
                }

                // THEN set the property accessible for this test only.
                $property->setAccessible(true);

                // Get the actual value of this property instance.
                $value = $property->getValue($this);

                // IF the value of this property is NULL, THEN we need to mark it as missing.
                if($required && $value === null)
                    $missing[] = $name;
            }

            if (!array_key_exists(ucfirst($method)."Required", $params) &&
                !array_key_exists($method."-required", $params) &&
                !array_key_exists(ucfirst($method), $params) &&
                !array_key_exists($method, $params) && ($method !== "post" && $name !== "id"))
            {
                // Unset this property, as it should not be passed to the Endpoint!
                unset($this->{$name});

                $ignored[] = $name;
            }
        }

        // If there are any required properties missing, then return false!
        return ($missing === []);
    }

}
