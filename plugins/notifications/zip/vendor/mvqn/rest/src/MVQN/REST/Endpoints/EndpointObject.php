<?php
declare(strict_types=1);

namespace MVQN\REST\Endpoints;

use MVQN\Annotations\AnnotationReader;
use MVQN\Collections\Collection;
use MVQN\Common\{Arrays, Patterns};

use MVQN\REST\{RestObject, RestClient};

/**
 * Class EndpointObject
 *
 * @package UCRM\REST\Endpoints
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
abstract class EndpointObject extends RestObject
{
    /** @const int The default JSON options for use when caching the annotations. */
    private const CACHE_JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    // =================================================================================================================
    // PROPERTIES (GLOBAL)
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @var int $id
     * @unique
     */
    protected $id;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    // =================================================================================================================
    // CREATE (GLOBAL)
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Attempts to INSERT this EndpointObject, using the class's annotated information via a HTTP POST Request.
     *
     * @return self Returns an EndpointObject of the same child object that called this method.
     *
     * @throws \Exception
     */
    public function insert(): self
    {
        /** @var self $data */
        $data = $this;

        if(!$this->validate("post", $missing))
        {
            throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] Annotations for the '".get_class($this)."' class require valid values be set ".
                "on all of the following properties before attempting an insert():\n> ".implode("\n> ", $missing)."\n");
        }

        /** @var self $endpoint */
        $endpoint = self::post($data);
        return $endpoint;
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Sends a HTTP POST Request on behalf of the EndpointObject, given optional parameters.
     *
     * @param EndpointObject $data The data to be passed in the body of the request.
     * @param array $params An optional set of parameters to be interpolated into the URL, when placeholders are used.
     * @return EndpointObject Returns the newly POSTed EndpointObject.
     *
     * @throws \Exception
     */
    public static function post(EndpointObject $data, array $params = []): EndpointObject
    {
        $class = get_called_class();

        // Instantiate an AnnotationReader for this class AND check for the important parameters.
        $annotations = new AnnotationReader($class);
        $endpoints = $annotations->getClassAnnotation("Endpoint");

        $endpoint = array_key_exists("post", $endpoints) ? $endpoints["post"] : "";

        if($endpoint === "")
            throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] An annotation like '@Endpoint { \"post\": \"/examples\" }' on the '$class' ".
                "class must be declared in order to resolve this endpoint'");

        // Interpolate the URL patterns against any provided parameters.
        $endpoint = Patterns::interpolateUrl($endpoint, $params);

        // Get an array of all Model properties, with any that do not belong in the POST method removed!
        $data = ($data !== null) ? $data->toArray("post") : [];

        // Attempt to POST the specified EndpointObject.
        $response = RestClient::post($endpoint, $data);

        // IF the response is empty, something went VERY wrong!
        if($response === [])
        {
            throw new \Exception("WTF???");
            //return [];
        }

        // HANDLE ANY ERROR CODES HERE...
        if(array_key_exists("code", $response))
        {
            switch($response["code"])
            {
                case 401: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The REST Client was not authorized to make this request!");
                case 403: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The provided App Key does not have sufficient privileges!");
                case 404: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] EndpointObject '$endpoint' was not found for class '$class'!");
                case 422: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] Data for endpoint '$endpoint' was improperly formatted!\n".
                    $response["message"]."\n".
                    json_encode($response["errors"], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                );

                // TODO: Add other response codes, as they are encountered!

                default:  break; // Likely the key "code" from an actual EndpointObject!
            }
        }

        // Finally, return the instantiated EndpointObject!
        return new $class($response);
    }

    // =================================================================================================================
    // READ (GLOBAL)
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Sends an HTTP GET Request using the calling class's annotated information, for all objects at this EndpointObject.
     *
     * @param string $override An optional URL to override the class's annotated endpoint information.
     * @param array $params An optional set of parameters to be interpolated into the URL, when placeholders are used.
     * @param array $query An optional set of query parameters to append to the end of the EndpointObject URL.
     * @return Collection Returns a Collection of EndpointObject objects; empty if none are found.
     *
     * @throws \Exception
     */
    public static function get(string $override = "", array $params = [], array $query = []): Collection
    {
        // Get a reference to the type of EndpointObject calling this function.
        $class = get_called_class();

        // THEN instantiate an AnnotationReader for this class AND check for the important parameters.
        $annotations = new AnnotationReader($class);
        $endpoints = $annotations->getClassAnnotation("Endpoint");
        $excludeId = $annotations->hasClassAnnotation("ExcludeId");
        $cached = $annotations->hasClassAnnotation("Cached");
        //$collection = $annotations->getClassAnnotation("Collection");

        // IF no override path has been provided...
        if($override === "")
        {
            // Make certain we have found a valid set of GET annotations, or throw an error!
            if (!array_key_exists("get", $endpoints) || $endpoints["get"] === "")
                throw new \Exception("[MVQN\REST\Endpoints\Endpoint] An '@Endpoint { \"get\": \"/examples\" }' annotation on the class must " .
                    "be declared in order to resolve this endpoint'");


            if(RestClient::cacheDir() !== null && $cached)
            {
                $file = RestClient::cachePath($class);

                if(file_exists($file))
                {
                    $objects = json_decode(file_get_contents($file), true);
                    unset($objects["_cached"]);
                    $objects = array_values($objects);

                    $endpoints = new Collection($class, $objects);

                    return $endpoints;
                }

            }

            // Interpolate the URL patterns against any provided parameters.
            $endpoint = Patterns::interpolateUrl($endpoints["get"], $params);
        }
        else
        {
            // Interpolate the overridden URL pattern against any provided parameters.
            $endpoint = Patterns::interpolateUrl($override, $params);
        }

        // Append any provided suffixes to the URL (i.e. query string).
        if($query !== [])
        {
            $pairs = [];

            foreach($query as $key => $value)
                $pairs[] = "$key=$value";

            $endpoint .= "?".implode("&", $pairs);
        }

        // Attempt to GET the specified EndpointObject.
        $response = RestClient::get($endpoint);

        // IF the response is empty...
        if($response === [])
            // THEN return an empty collection, as we got nothing back!
            return new Collection($class, []);

        // HANDLE ANY ERROR CODES HERE...
        if(array_key_exists("code", $response))
        {
            switch($response["code"])
            {
                case 401: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The REST Client was not authorized to make this request!");
                case 403: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The provided App Key does not have sufficient privileges!");
                case 404: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] EndpointObject '$endpoint' was not found for class '$class'!");

                case 500: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] EndpointObject '$endpoint' returned: '{$response["message"]}'!");

                // TODO: Add other response codes, as they are encountered!

                default:  break; // Likely the key "code" from an actual EndpointObject!
            }
        }

        // Handle shifting any single object responses into a single indexed array for further processing.
        $response = Arrays::is_assoc($response) ? [ $response ] : $response;

        // Create a collection to store the object versions of the response.
        $endpoints = new Collection($class);

        // Loop through each resulting object...
        foreach($response as $object)
        {
            $classObject = new $class($object);

            // Remove the ID property, if the '@excludeId' annotation was set on this class.
            if($excludeId)
                unset($classObject->id);

            // Add the newly instantiated EndpointObject to the collection.
            $endpoints->push($classObject);
        }

        if(RestClient::cacheDir() !== null && $cached)
        {
            $file = RestClient::cachePath($class);

            if (!file_exists(dirname($file)))
                mkdir(dirname($file), 0777, true);

            $elements = [];

            foreach($endpoints->elements() as $element)
            {
                /** @var EndpointObject $element */
                $id = $element->getId();
                $elements[$id] = $element;
            }

            $elements["_cached"] = (new \DateTime())->format("c");

            file_put_contents($file, json_encode($elements, self::CACHE_JSON_OPTIONS));
        }



        // Finally, return the collection of EndpointObjects!
        return $endpoints;
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Sends an HTTP GET Request using the calling class's annotated information, for a single object, given the ID.
     *
     * @param int|null $id The ID of the EndpointObject for which to retrieve.
     * @return EndpointObject|null Returns the EndpointObject, or NULL if the object can not be found with this ID.
     *
     * @throws \Exception
     */
    public static function getById(?int $id): ?EndpointObject
    {
        if($id === null)
            return null;

        // Get a reference to the type of EndpointObject calling this function.
        $class = get_called_class();

        // Instantiate an AnnotationReader for this class AND check for the important parameters.
        $annotations = new AnnotationReader($class);
        $endpoints = $annotations->getClassAnnotation("Endpoint");

        // Make certain we have found a valid set of GET annotations, or throw an error!
        if(!array_key_exists("getById", $endpoints))
            throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] An '@Endpoint { \"getById\": \"/examples/:id\" }' annotation on ".
                "the '$class' class must be declared in order to resolve this endpoint'");

        $cached = $annotations->hasClassAnnotation("Cached");

        if(RestClient::cacheDir() !== null && $cached)
        {
            $file = RestClient::cachePath($class);

            if(file_exists($file))
            {
                $objects = json_decode(file_get_contents($file), true);
                //unset($objects["_cached"]);
                //$objects = array_values($objects);
                $object = $objects[$id];

                $endpoint = new $class($object);

                return $endpoint;
            }

        }

        // Interpolate the URL patterns against any provided parameters.
        $endpoint = Patterns::interpolateUrl($endpoints["getById"], [ "id" => $id ]);

        // Attempt to GET the specified EndpointObject.
        $response = RestClient::get($endpoint);

        // IF the response is empty...
        if($response === [])
            // THEN return NULL, as we got nothing back!
            return null;

        // HANDLE ANY ERROR CODES HERE...
        if(array_key_exists("code", $response))
        {
            switch($response["code"])
            {
                case 401: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The REST Client was not authorized to make this request!");
                case 403: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The provided App Key does not have sufficient privileges!");
                case 404: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] EndpointObject '$endpoint' was not found for class '$class'!");

                // TODO: Add other response codes, as they are encountered!

                default:  break; // Likely the key "code" from an actual EndpointObject!
            }
        }

        // Finally, return the instantiated EndpointObject!
        return new $class($response);
    }

    // =================================================================================================================
    // UPDATE (GLOBAL)
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Attempts to UPDATE this EndpointObject, using the class's annotated information via a HTTP PATCH Request.
     *
     * @return self Returns an EndpointObject of the same child object that called this method.
     *
     * @throws \Exception
     */
    public function update(): self
    {
        /** @var self $data */
        $data = $this;

        if(!$this->validate("patch", $missing))
        {
            throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] Annotations for the '".get_class($this)."' class require valid values be set ".
                "on all of the following properties before attempting an update():\n> ".implode("\n> ", $missing)."\n");
        }

        /** @var self $endpoint */
        $endpoint = self::patch($data, [ "id" => $this->getId() ]);

        return $endpoint;
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Sends a HTTP PATCH Request on behalf of the EndpointObject, given optional parameters and a suffix, if desired.
     *
     * @param EndpointObject|null $data The data to be passed in the body of the request, can be NULL for EXEC endpoints.
     * @param array $params An optional set of parameters to be interpolated into the URL, when placeholders are used.
     * @param string $suffix An optional suffix to append to the end of the EndpointObject URL.
     * @return EndpointObject|null Returns the newly patched EndpointObject, or NULL in the case of failure or no data required.
     *
     * @throws \Exception
     */
    public static function patch(?EndpointObject $data, array $params = [], string $suffix = ""): ?EndpointObject
    {
        // Get a reference to the type of EndpointObject calling this function.
        $class = get_called_class();

        // Instantiate an AnnotationReader for this class AND check for the important parameters.
        $annotations = new AnnotationReader($class);
        $endpoints = $annotations->getClassAnnotation("Endpoint");

        $endpoint = array_key_exists("patch", $endpoints) ? $endpoints["patch"] : null;

        if($endpoint === null || $endpoint === [])
            throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] An annotation like '@Endpoint { \"patch\": \"/examples/:id\" }' on the ".
                "'$class' class must be declared in order to resolve this endpoint'");

        // Interpolate the URL patterns against any provided parameters.
        $endpoint = Patterns::interpolateUrl($endpoints["patch"], $params);

        // Append any provided suffixes to the URL.
        if($suffix !== "")
            $endpoint .= $suffix;

        // Get an array of all Model properties, with any that do not belong in the PATCH method removed!
        $data = ($data !== null) ? $data->toArray("patch") : [];

        // Attempt to PATCH the specified EndpointObject.
        $response = RestClient::patch($endpoint, $data);

        // IF the response is empty, something went VERY wrong!
        if($response === [])
        {
            throw new \Exception("WTF???");
            //return [];
        }

        // HANDLE ANY ERROR CODES HERE...
        if(array_key_exists("code", $response))
        {
            switch($response["code"])
            {
                case 401: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The REST Client was not authorized to make this request!");
                case 403: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The provided App Key does not have sufficient privileges!");
                case 404: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] EndpointObject '$endpoint' was not found for class '$class'!");
                case 422: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] Data for endpoint '$endpoint' was improperly formatted!\n".
                    $response["message"]."\n".
                    json_encode($response["errors"], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                );

                // TODO: Add other response codes, as they are encountered!

                default:  break; // Likely the key "code" from an actual EndpointObject!
            }
        }

        // Finally, return the instantiated EndpointObject!
        return new $class($response);
    }






    public function remove(): self
    {
        /** @var self $data */
        $data = $this;

        //if(!$this->validate("delete", $missing))
        //{
        //    throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] Annotations for the '".get_class($this)."' class require valid values be set ".
        //        "on all of the following properties before attempting an update():\n> ".implode("\n> ", $missing)."\n");
        //}

        /** @var self $endpoint */
        $endpoint = self::delete($data, [ "id" => $this->getId() ]);

        return $endpoint;
    }




    public static function delete(?EndpointObject $data, array $params = [], string $suffix = ""): bool
    {
        // Get a reference to the type of EndpointObject calling this function.
        $class = get_called_class();

        // Instantiate an AnnotationReader for this class AND check for the important parameters.
        $annotations = new AnnotationReader($class);
        $endpoints = $annotations->getClassAnnotation("Endpoint");

        $endpoint = array_key_exists("delete", $endpoints) ? $endpoints["delete"] : null;

        if($endpoint === null || $endpoint === [])
            throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] An annotation like '@Endpoint { \"delete\": \"/examples/:id\" }' on the ".
                "'$class' class must be declared in order to resolve this endpoint'");

        // Interpolate the URL patterns against any provided parameters.
        $endpoint = Patterns::interpolateUrl($endpoints["delete"], $params);

        // Append any provided suffixes to the URL.
        if($suffix !== "")
            $endpoint .= $suffix;

        // Get an array of all Model properties, with any that do not belong in the PATCH method removed!
        //$data = ($data !== null) ? $data->toArray("delete") : [];

        // Attempt to PATCH the specified EndpointObject.
        $response = RestClient::delete($endpoint);

        // IF the response is empty, something went VERY wrong!
        //if($response === [])
        //{
        //    throw new \Exception("WTF???");
        //    //return [];
        //}

        // HANDLE ANY ERROR CODES HERE...
        if(array_key_exists("code", $response))
        {
            switch($response["code"])
            {
                case 401: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The REST Client was not authorized to make this request!");
                case 403: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] The provided App Key does not have sufficient privileges!");
                case 404: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] EndpointObject '$endpoint' was not found for class '$class'!");
                case 422: throw new \Exception("[MVQN\REST\Endpoints\EndpointObject] Data for endpoint '$endpoint' was improperly formatted!\n".
                    $response["message"]."\n".
                    json_encode($response["errors"], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                );

                // TODO: Add other response codes, as they are encountered!

                default:  break; // Likely the key "code" from an actual EndpointObject!
            }
        }

        // Finally, return the instantiated EndpointObject!
        return true;
    }


}
