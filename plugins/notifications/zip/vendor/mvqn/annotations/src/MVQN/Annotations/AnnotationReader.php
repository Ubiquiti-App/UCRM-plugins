<?php
declare(strict_types=1);

namespace MVQN\Annotations;


use MVQN\Common\Strings;

/**
 * Class AnnotationReader
 *
 * @package MVQN\Annotations
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class AnnotationReader
{
    // =================================================================================================================
    // CONSTANTS
    // -----------------------------------------------------------------------------------------------------------------

    /** @const int The default JSON options for use when caching the annotations. */
    private const CACHE_JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    /** @const int Denote the annotation about to be parsed is on a class declaration. */
    private const PARSE_STYLE_CLASS = 1;
    /** @const int Denote the annotation about to be parsed is on a method declaration. */
    private const PARSE_STYLE_METHOD = 2;
    /** @const int Denote the annotation about to be parsed is on a property declaration. */
    private const PARSE_STYLE_PROPERTY = 4;

    // All patterns used for matching the annotations!
    private const ANNOTATION_PATTERN              = '/(?:\*)(?:[\t ]*)?@([\w\_\-\\\\]+)(?:[\t ]*)?(.*)$/m';
    //private const ANNOTATION_PATTERN_JSON         = '/(\{.*\})/';
    //private const ANNOTATION_PATTERN_ARRAY        = '/(\[.+\])/';
    //private const ANNOTATION_PATTERN_EVAL         = '/\`(.*)\`/';
    //private const ANNOTATION_PATTERN_ARRAY_NAMED  = '/([\w\_\-]*)(?:\[\])/';
    //private const ANNOTATION_PATTERN_VAR_TYPE     = '/^([\w\|\[\]\_]+)\s*(?:\$(\w+))?(.*)?/';
    //private const ANNOTATION_PATTERN_PROPERTY     = '/^property-*(read|write|)\s+(\w+)\s+(\$\w+)\s+(.*)$/';

    // =================================================================================================================
    // PROPERTIES
    // -----------------------------------------------------------------------------------------------------------------

    /** @var string The name of the class, for which any class, method or property annotations exist, to be parsed. */
    protected $class = "";

    /** @var array A cached array of 'class' names to their fully qualified namespace, including 'use' statements. */
    protected $uses = [];

    /** @var string An optional directory to use for caching the annotation results for later lookup. */
    protected static $cachePath = null;

    private const CACHE_FOLDER = ".cache/mvqn/annotations";

    // =================================================================================================================
    // CONSTRUCTOR/DESTRUCTOR
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param string $class The name of the class for which to perform all reflections and annotation parsing.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->uses = $this->getUseStatements();
    }

    // =================================================================================================================
    // STATIC METHODS: Caching
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param string|null $path If provided, sets the cache directory for annotations from this point forward.
     * @return string|null Returns the directory path, or NULL if caching is disabled.
     */
    public static function cacheDir(string $path = null): ?string
    {
        if($path !== null)
        {
            // Create the cache directory, if it does not exist...
            if (!file_exists(dirname($path)))
                mkdir(dirname($path), 0777, true);

            // Create a '.cache' directory inside the cache directory, if it does not exist...
            if (!file_exists(dirname($path."/".self::CACHE_FOLDER."/")))
                mkdir(dirname($path."/".self::CACHE_FOLDER."/"), 0777, true);

            // Set the cache path, statically, for future use.
            self::$cachePath = $path;
        }

        // Return the cache path, even if it is NULL!
        return self::$cachePath;
    }

    /**
     * @param string $directory The current directory to remove.
     * @internal
     */
    private static function removeDirectoryRecursive(string $directory)
    {
        if (is_dir($directory))
        {
            foreach (scandir($directory) as $object)
            {
                if ($object !== "." && $object !== "..")
                {
                    if (is_dir($directory."/".$object))
                        self::removeDirectoryRecursive($directory."/".$object);
                    else
                        unlink($directory."/".$object);
                }
            }

            rmdir($directory);
        }
    }

    /**
     * @param array|null $classes An optional array of class names for which to remove, ignoring the rest.
     */
    public static function cacheClear(array $classes = null): void
    {
        if(self::$cachePath !== null)
        {
            // IF no specific classes have been provided
            if($classes === null)
            {
                // Use a specific '.cache' directory as to avoid accidentally deleting undesired folders recursively.
                self::removeDirectoryRecursive(self::$cachePath."/".self::CACHE_FOLDER."/");
                return;
            }

            // Loop through each provided class and attempt to delete them individually...
            foreach($classes as $class)
            {
                $cacheFile = self::$cachePath."/".self::CACHE_FOLDER."/".$class;

                if(file_exists($cacheFile))
                    self::removeDirectoryRecursive($cacheFile);
            }
        }
    }


    public static function hasMethodAnnotationsCached(string $class, string $method): bool
    {
        if(self::$cachePath === null)
            return false;

        // Generate the full filename and path for caching the current Annotation.
        $cacheFile = self::$cachePath."/".self::CACHE_FOLDER."/$class/method.$method.json";

        return file_exists($cacheFile);
    }

    public static function getMethodAnnotationsCached(string $class, string $method): ?array
    {
        if(!self::hasMethodAnnotationsCached($class, $method))
            return null;

        // Generate the full filename and path for caching the current Annotation.
        $cacheFile = self::$cachePath."/".self::CACHE_FOLDER."/$class/method.$method.json";

        return json_decode(file_get_contents($cacheFile), true);
    }






    // =================================================================================================================
    // METHODS: Parsing
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param int $target
     * @param string $docBlock
     * @param string $name
     * @return array
     * @throws \Exception
     */
    private function parse(int $target, string $docBlock, string $name = ""): array
    {
        // Set appropriate filename for caching, based on the target type.
        switch($target)
        {
            //case Annotation::TARGET_NONE:
            case Annotation::TARGET_CLASS:
                $targetString = "class";
                break;
            case Annotation::TARGET_METHOD:
                $targetString = "method.$name";
                break;
            case Annotation::TARGET_PROPERTY:
                $targetString = "property.$name";
                break;
            //case Annotation::TARGET_ANY:
            default:
                $targetString = "ERROR";
                break;
        }

        // Generate the full filename and path for caching the current Annotation.
        $cacheFile = self::$cachePath !== null ? self::$cachePath."/".self::CACHE_FOLDER."/{$this->class}/$targetString.json" : "";

        // IF using caching...
        if(self::$cachePath !== null && file_exists($cacheFile))
        {
            // THEN simply return the cached results!
            $params = json_decode(file_get_contents($cacheFile), true);
            return $params;
        }

        // Build a collection of valid lines ONLY!  IF none exist, simply return empty-handed...
        if(!preg_match_all(self::ANNOTATION_PATTERN, $docBlock, $matches))
            return [];

        // Create a collection to store valid mappings.
        $params = [];

        // Load the included Standard Annotations.
        $standard = Annotation::getStandardAnnotations();

        // Loop through each matched annotation...
        for($i = 0; $i < count($matches[0]); $i++)
        {
            $key = $matches[1][$i];
            $value = trim($matches[2][$i]); // Remove trailing '\r' that we cannot seem to RegEx out of there!

            // Check for an Annotation class name...
            if(Strings::startsWithUpper($key) || Strings::contains($key, "\\") || array_key_exists($key, $standard))
            {
                if(array_key_exists($key, $standard))
                    $annotationClass = $standard[$key];
                else
                    $annotationClass = $this->findAnnotationClass($key);

                if($annotationClass !== null)
                {
                    $occurrences = defined("$annotationClass::SUPPORTED_DUPLICATES") ?
                        $annotationClass::SUPPORTED_DUPLICATES : true;

                    if(!$occurrences && array_key_exists($key, $params))
                        throw new \Exception("An annotation for '@$key' has already been declared in the set of ".
                            "annotations for '$annotationClass".($name !== "" ? "::$name" : "")."'!");

                    /** @var Annotation $instance */
                    $instance = new $annotationClass($target, $annotationClass, $name, $key, $value);
                    $params = $instance->parse($params); //, $annotationName);
                    continue;
                }
            }

            // HANDLE all other non-Annotation extended annotations now...

            // Count the total number of occurrences of this particular '@<keyword>'.
            $count = count(array_keys($matches[1], $key, true));

            /*
            // Handle JSON objects!
            if(preg_match(self::ANNOTATION_PATTERN_JSON, $value, $match))
            {
                $value = json_decode($value, true);
            }
            else
                // Handle Array objects!
                if(preg_match(self::ANNOTATION_PATTERN_ARRAY, $value, $match))
                {
                    // TODO: Determine best way to handle the cases where a property has a type of Type[]!

                    // For now, we just remove the [] at the end of the type name...
                    if(preg_match(self::ANNOTATION_PATTERN_ARRAY_NAMED, $value, $named_match))
                        $value = str_replace("[]", "", $value);

                    $value = eval("return ".$value.";");
                }
                else
                    // Handle Eval objects!
                    if(preg_match(self::ANNOTATION_PATTERN_EVAL, $value, $match))
                    {
                        // TODO: Determine the best way to handle this scenario!
                        //$value = eval("return ".$value.";");
                        echo "";
                    }

            // Cleanup both arrays and JSON values, removing leading and trailing whitespace.
            $value = is_array($value) ? array_map("trim", $value) : trim($value);
            */

            // IF there is more than one occurrence of this @<keyword>...
            if($count > 1)
            {
                // THEN check to see if this is the first occurrence...
                if(!array_key_exists($key, $params))
                {
                    // AND append this value directly to the array, if it is!
                    $params[$key] = $value;
                }
                else
                {
                    // OTHERWISE, append this value to the existing array!

                    // IF the current value is NOT an array...
                    if(!is_array($value))
                    {
                        // THEN, assume the other values under this @<param> are also NOT arrays...
                        if(!is_array($params[$key]))
                        {
                            $oldValue = $params[$key];
                            $params[$key] = [];
                            $params[$key][] = $oldValue;
                        }

                        $params[$key][] = $value;
                    }
                    else
                    {
                        $params[$key] = array_merge($params[$key], $value);
                    }

                }
            }
            else
            {
                // OTHERWISE, this is the only occurrence, simply append it to the array!
                $params[$key] = $value;
            }

        }

        // Cache the results, if using caching...
        if(self::$cachePath !== null)
        {
            if (!file_exists(dirname($cacheFile)))
                mkdir(dirname($cacheFile), 0777, true);

            $params["_cached"] = (new \DateTime())->format("c");

            file_put_contents($cacheFile, json_encode($params, self::CACHE_JSON_OPTIONS));
        }

        return $params;
    }

    /**
     * @return array Returns an array containing the pairing between class/alias and fully qualified class name.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getUseStatements(): array
    {
        $file = $this->getReflectedClass()->getFileName();
        $tokens = token_get_all(file_get_contents($file));

        $uses = [];
        $building = false;
        $current = "";

        foreach($tokens as $token)
        {
            // Check to see if we've encountered the class declaration...
            if(is_array($token) && $token[0] === T_CLASS)
                // And break if we have, as we do not want to search beyond here!
                break;

            // Check to see if the current token is the "use" statement...
            if(is_array($token) && $token[0] === T_USE)
            {
                // And if so, start building the namespace.
                $building = true;
                $current = "";
            }

            // Keep appending tokens as long as they are part of the 'use' statement...
            if(is_array($token) && $token[0] !== T_USE && $building)
                $current .= $token[1];

            // Check to see if a semicolon is reached while building the 'use' statement...
            if(!is_array($token) && $token === ";" && $building)
            {
                $building = false;

                // Handle situations where 'as' is used...
                if(Strings::contains($current, " as "))
                {
                    $parts = array_map("trim", explode(" as ", $current));
                    // Add the current class => namespace mapping to the collection.
                    $uses[$parts[1]] = $parts[0];
                }
                else
                {
                    $parts = array_map("trim", explode("\\", $current));
                    // Add the current class => namespace mapping to the collection.
                    $uses[$parts[count($parts) - 1]] = trim($current);
                }
            }
        }

        // Return the array of 'use' pairs of class => namespace!
        return $uses;
    }

    /**
     * @return string Returns the namespace part of the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getNamespace(): string
    {
        return $this->getReflectedClass()->getNamespaceName();
    }

    /**
     * Returns the fully qualified class name, even if a non-qualified or aliased class name is provided.
     *
     * @param string $class The class name to be used for the lookup.
     * @return string|null Returns the fully qualified class name or NULL if a valid Annotation class is not found.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function findAnnotationClass(string $class): ?string
    {
        $annotationClass = "";

        // Handle fully qualified class names!
        if(Strings::startsWith($class, "\\"))
        {
            $annotationClass = $class;
        }

        // Handle exact class name matches, including aliases classes...
        if(array_key_exists($class, $this->uses))
            $annotationClass = $this->uses[$class];

        // Handle fully qualified class names...
        if($annotationClass === "" && in_array($class, $this->uses))
        {
            $key = array_search($class, $this->uses);
            $annotationClass = $this->uses[$key];
        }

        // Handle class names living in the same namespace...
        if($annotationClass === "") // && class_exists($namespace."\\".$class))
            $annotationClass = $this->getNamespace()."\\".$class;

        // Make certain the class exists before continuing...
        if ($annotationClass !== "" && class_exists($annotationClass))
        {
            // IF the current annotation class does not extend Annotation...
            if (!is_subclass_of($annotationClass, Annotation::class, true)) {
                // THEN, assume it is handled by another library and return NULL!
                //return null;
            }

            // Return the fully qualified class, if nothing went wrong!
            return $annotationClass;
        }

        // IF we have an annotation class name, but the class was not found...
        if ($annotationClass !== "" && class_exists($annotationClass."Annotation") && !Strings::contains($annotationClass, "\\"))
        {
            // THEN, try again with the 'Annotation' suffix!
            return $this->findAnnotationClass($class."Annotation");
        }

        // This is not a known class, assume a user-defined annotation and return NULL!
        return null;
    }

    // =================================================================================================================
    // METHODS: Reflection
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return \ReflectionClass Returns the current class, using the Reflection engine.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getReflectedClass()
    {
        $class = new \ReflectionClass($this->class);
        return $class;
    }

    /**
     * @param int|null $filter An optional filter to be used to get only certain types of methods.
     * @return array Returns the methods of the current class, given an optional filter, using the Reflection engine.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getReflectedMethods(int $filter = null): array
    {
        $class = new \ReflectionClass($this->class);
        return !$filter ? $class->getMethods() : $class->getMethods($filter);
    }

    /**
     * @param string $method The name of a specific method to retrieve.
     * @return \ReflectionMethod Returns a method of the current class, given a name, using the Reflection engine.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getReflectedMethod(string $method)
    {
        $class = new \ReflectionClass($this->class);
        return $class->getMethod($method);
    }

    /**
     * @param int|null $filter An optional filter to be used to get only certain types of properties.
     * @return array Returns the properties of the current class, given an optional filter, using the Reflection engine.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getReflectedProperties(int $filter = null): array
    {
        $class = new \ReflectionClass($this->class);
        return !$filter ? $class->getProperties() : $class->getProperties($filter);
    }

    /**
     * @param string $property The name of a specific property to retrieve.
     * @return \ReflectionProperty Returns a property of the current class, given a name, using the Reflection engine.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getReflectedProperty(string $property)
    {
        $class = new \ReflectionClass($this->class);
        return $class->getProperty($property);
    }

    // =================================================================================================================
    // METHODS: Annotations
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return array Returns an associative array of all class, method and property annotations for the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getAnnotations(): array
    {
        $annotations = [];

        $annotations["class"] = $this->getClassAnnotations();
        $annotations["methods"] = $this->getMethodAnnotations();
        $annotations["properties"] = $this->getPropertyAnnotations();

        return $annotations;
    }

    // =================================================================================================================
    // METHODS: Class Annotations
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return array Returns an associative array of all annotations for the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getClassAnnotations(): array
    {
        if(AnnotationReader::cacheDir() !== null)
        {
            $path = AnnotationReader::cacheDir()."/".self::CACHE_FOLDER."/".$this->class;
            $file = "class.json";

            if(file_exists($path."/".$file))
            {
                $annotations = json_decode(file_get_contents($path."/".$file), true);
                return $annotations;
            }
        }

        $docBlock = $this->getReflectedClass()->getDocComment();
        $params = $docBlock ? $this->parse(self::PARSE_STYLE_CLASS, $docBlock) : [];
        return $params;
    }

    /**
     * @param string $keyword The keyword of an annotation for this class.
     * @return mixed Returns the value of the specified annotation for the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getClassAnnotation(string $keyword)//: array
    {
        $params = $this->getClassAnnotations();
        return array_key_exists($keyword, $params) ? $params[$keyword] : [];
    }

    /**
     * @param string $pattern A pattern for which to search the keywords of annotations for this class.
     * @return array Returns an associative array of any matching annotations for the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getClassAnnotationsLike(string $pattern): array
    {
        $params = $this->getClassAnnotations();

        $matches = [];

        foreach($params as $key => $value)
            if(preg_match($pattern, $key))
                $matches[$key] = $value;

        return $matches;
    }

    /**
     * @param string $keyword The keyword of an annotation for this class.
     * @return bool Returns TRUE if the class annotation exists, otherwise FALSE.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function hasClassAnnotation(string $keyword): bool
    {
        //$params = $this->getClassAnnotations();
        //return array_key_exists($name, $params);
        return $this->getClassAnnotation($keyword) !== [];
    }

    // =================================================================================================================
    // METHODS: Method Annotations
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param string[] $methods
     * @return array Returns an associative array of all annotations for the method(s) of the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getMethodAnnotations(string ...$methods): array
    {
        if($methods === [])
        {
            foreach($this->getReflectedMethods() as $reflectedMethod)
            {
                /** @var \ReflectionMethod $reflectedMethod */
                $methods[] = $reflectedMethod->getName();
            }
        }

        $annotations = [];

        foreach($methods as $method)
        {
            $missing = true;

            // IF caching is enabled...
            if(AnnotationReader::cacheDir() !== null)
            {
                $path = AnnotationReader::cacheDir()."/".self::CACHE_FOLDER."/".$this->class;
                $file = $path."/method.$method.json";

                if(file_exists($file))
                {
                    $params = json_decode(file_get_contents($file), true);

                    if(json_last_error() === JSON_ERROR_NONE)
                    {
                        $missing = false;
                        $annotations[$method] = $params;
                    }
                }
            }

            if($missing)
            {
                $docBlock = $this->getReflectedMethod($method)->getDocComment();
                $params = $docBlock ? $this->parse(self::PARSE_STYLE_METHOD, $docBlock, $method) : [];

                $annotations[$method] = $params;
            }
        }

        if(count($methods) === 1)
        {
            return $annotations[$methods[0]];
        }

        return $annotations;
    }

    /**
     * @param string $method The method of the current class for which to examine.
     * @param string $keyword The keyword of an annotation for this method.
     * @return mixed Returns the value of the annotation for the given method of the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getMethodAnnotation(string $method, string $keyword)//: array
    {
        $annotations = $this->getMethodAnnotations($method);
        return array_key_exists($keyword, $annotations) ? $annotations[$keyword] : [];
    }

    /**
     * @param string $method The method of the current class for which to examine.
     * @param string $pattern A pattern for which to search the keywords of annotations for this method.
     * @return array Returns an associative array of matching annotations for the given method of the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getMethodAnnotationsLike(string $method, string $pattern): array
    {
        $annotations = $this->getMethodAnnotations($method);
        //$matches = preg_grep($pattern, $annotations);

        $matches = [];
        foreach($annotations as $key => $value)
            if(preg_match($pattern, $key))
                $matches[$key] = $value;

        return $matches;
    }

    /**
     * @param string $method The method of the current class for which to examine.
     * @param string $keyword The keyword of an annotation for this method.
     * @return bool Returns TRUE if the method annotation exists, otherwise FALSE.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function hasMethodAnnotation(string $method, string $keyword): bool
    {
        //$annotations = $this->getMethodAnnotations($method);
        //return array_key_exists($keyword, $annotations);
        return $this->getMethodAnnotation($method, $keyword) !== [];
    }

    // =================================================================================================================
    // METHODS: Property Annotations
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param string[] $properties
     * @return array Returns an associative array of all annotations for the property/properties of the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getPropertyAnnotations(string ...$properties): array
    {
        if($properties === [])
        {
            foreach($this->getReflectedProperties() as $reflectedProperty)
            {
                /** @var \ReflectionProperty $reflectedProperty */
                $properties[] = $reflectedProperty->getName();
            }
        }

        $annotations = [];

        foreach($properties as $property)
        {
            $missing = true;

            // IF caching is enabled...
            if(AnnotationReader::cacheDir() !== null)
            {
                $path = AnnotationReader::cacheDir()."/".self::CACHE_FOLDER."/".$this->class;
                $file = $path."/property.$property.json";

                if(file_exists($file))
                {
                    $params = json_decode(file_get_contents($file), true);

                    if(json_last_error() === JSON_ERROR_NONE)
                    {
                        $missing = false;
                        $annotations[$property] = $params;
                    }
                }
            }

            if($missing)
            {
                $docBlock = $this->getReflectedProperty($property)->getDocComment();
                $params = $docBlock ? $this->parse(self::PARSE_STYLE_PROPERTY, $docBlock, $property) : [];

                $annotations[$property] = $params;
            }
        }

        if(count($properties) === 1)
        {
            return $annotations[$properties[0]];
        }

        return $annotations;
    }

    /**
     * @param string $property The property of the current class for which to examine.
     * @param string $keyword The keyword of an annotation for this property.
     * @return mixed Returns the value of the annotation for the give property of the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getPropertyAnnotation(string $property, string $keyword)//: array
    {
        $params = $this->getPropertyAnnotations($property);
        return array_key_exists($keyword, $params) ? $params[$keyword] : [];
    }

    /**
     * @param string $property The property of the current class for which to examine.
     * @param string $pattern A pattern for which to search the keywords of annotations for this property.
     * @return array Returns an associative array of matching annotations for the given property of the current class.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function getPropertyAnnotationsLike(string $property, string $pattern): array
    {
        $annotations = $this->getPropertyAnnotations($property);
        //$matches = preg_grep($pattern, $params);

        $matches = [];
        foreach($annotations as $key => $value)
            if(preg_match($pattern, $key))
                $matches[$key] = $value;

        return $matches;
    }

    /**
     * @param string $property The property of the current class for which to examine.
     * @param string $keyword The keyword of an annotation for this property.
     * @return bool Returns TRUE if the property annotation exists, otherwise FALSE.
     * @throws \ReflectionException Throws an Exception if there are any issues "reflecting" the object(s).
     */
    public function hasPropertyAnnotation(string $property, string $keyword): bool
    {
        //$annotations = $this->getPropertyAnnotations($property);
        //return array_key_exists($keyword, $annotations);
        return $this->getPropertyAnnotation($property, $keyword) !== [];
    }

}
