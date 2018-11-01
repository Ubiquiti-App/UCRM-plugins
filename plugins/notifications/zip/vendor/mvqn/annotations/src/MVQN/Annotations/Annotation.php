<?php
declare(strict_types=1);

namespace MVQN\Annotations;

/**
 * Class Annotation
 *
 * @package MVQN\Annotations
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
abstract class Annotation
{
    // =================================================================================================================
    // CONSTANTS
    // -----------------------------------------------------------------------------------------------------------------

    /** @const int Flag denoting that this annotation is not supported by any declarations. */
    public const TARGET_NONE = 0;
    /** @const int Flag denoting that this annotation is supported in class declarations. */
    public const TARGET_CLASS = 1;
    /** @const int Flag denoting that this annotation is supported in method declarations. */
    public const TARGET_METHOD = 2;
    /** @const int Flag denoting that this annotation is supported in property declarations. */
    public const TARGET_PROPERTY = 4;
    /** @const int Flag denoting that this annotation is supported for all declarations. */
    public const TARGET_ANY = 7;

    // =================================================================================================================
    // PROPERTIES
    // -----------------------------------------------------------------------------------------------------------------

    /** @var string $class The name of the class containing the current annotation. */
    protected $class = Annotation::class;

    /** @var int $target The target of the current annotation, for example: class, method or property. */
    protected $target = Annotation::TARGET_NONE;

    /** @var string $name The name of the current annotation's method or property.  Use "$class" for class name. */
    protected $name = "";

    /** @var string $keyword The keyword of the current annotation. */
    protected $keyword = "";

    /** @var string $value The "raw" value of the current annotation. */
    protected $value = "";

    // =================================================================================================================
    // CONSTRUCTOR
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param int $target The target of the current annotation, for example: class, method or property.
     * @param string $class The name of the class containing the current annotation.
     * @param string $name The name of the current annotation's method or property.  Use "$class" for class name.
     * @param string $keyword A valid annotation keyword that immediately follows the "@" symbol.
     * @param string $value Any characters following the annotation keyword, recognized as the annotation value.
     * @throws \Exception Throws an Exception, if this annotation class does not support the current annotation target.
     */
    public function __construct(int $target, string $class, string $name, string $keyword, string $value)
    {
        // Set the class properties for this annotation...
        $this->class = $class;
        $this->target = $target;
        $this->name = $name;
        $this->keyword = $keyword;
        $this->value = $value;

        // Determine the child class.
        $child = get_called_class();

        // Get any supported targets from the child class definition or default to any target.
        $supports = defined("$child::SUPPORTED_TARGETS") ? $child::SUPPORTED_TARGETS : Annotation::TARGET_ANY;

        // IF this annotation class does not support the current target...
        if(!(($supports & $target) === $target))
        {
            // THEN, set an informative error message...
            switch($target)
            {
                //case Annotation::TARGET_NONE:
                case Annotation::TARGET_CLASS:
                    $message = "classes";
                    break;
                case Annotation::TARGET_METHOD:
                    $message = "methods";
                    break;
                case Annotation::TARGET_PROPERTY:
                    $message = "properties";
                    break;
                //case Annotation::TARGET_ANY:
                default:
                    $message = "unknown types";
                    break;
            }

            // AND throw an Exception!
            throw new \Exception("[MVQN\Annotations\AnnotationReader] @{$this->keyword} is not supported on $message!");
        }

    }

    /**
     * @return array Returns an array of keyword => class associations from the included "Standard" Annotations.
     */
    public static function getStandardAnnotations(): array
    {
        // Create an empty array to store the associations.
        $annotations = [];

        // Loop through each file in the included "Standard" directory...
        foreach(scandir(__DIR__."/./Standard/") as $annotation)
        {
            // IF the file is one of the special "." or ".." files, THEN simply ignore and continue!
            if($annotation === "." || $annotation === "..")
                continue;

            // Convert the file name to the appropriate annotation keyword
            $name = str_replace("Annotation.php", "", $annotation);
            $name = lcfirst($name);

            // And then generate the formal class name.
            $class = ucfirst($name)."Annotation";

            // Finally, append the association withe the fully qualified class name to the array.
            $annotations[$name] = "MVQN\\Annotations\\Standard\\$class";
        }

        // And return the resulting array, even if it is empty!
        return $annotations;
    }

    // =================================================================================================================
    // ABSTRACTS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param array $existing Any existing annotations that were previously parsed from the same declaration.
     * @return array Returns an array of keyword => value(s) parsed by the implementing Annotation class.
     */
    public abstract function parse(array $existing): array;

}
