<?php
declare(strict_types=1);

namespace MVQN\Annotations\Standard;

use MVQN\Annotations\Annotation;

/**
 * Class ParamAnnotation
 *
 * @package MVQN\Annotations\Standard
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class ParamAnnotation extends Annotation
{
    /** @const int Denotes supported annotation targets, defaults to ANY when not explicitly provided! */
    public const SUPPORTED_TARGETS = Annotation::TARGET_METHOD;

    /** @const bool Denotes supporting multiple declarations of this annotation per block, defaults to TRUE! */
    public const SUPPORTED_DUPLICATES = true;

    /**
     * @param array $existing Any existing annotations that were previously parsed from the same declaration.
     * @return array Returns an array of keyword => value(s) parsed by this Annotation implementation.
     */
    public function parse(array $existing = []): array
    {
        $pattern = '/^([\w\|\[\]\_\\\]+)\s*(?:\$(\w+))?(.*)?$/';

        if(preg_match($pattern, $this->value, $matches))
        {
            $param = [];
            $param["types"] = explode("|", $matches[1]);
            $param["name"] = $matches[2] ?: $this->name;
            $param["description"] = $matches[3];

            $existing["param"][] = $param;
        }

        return $existing;
    }
}
