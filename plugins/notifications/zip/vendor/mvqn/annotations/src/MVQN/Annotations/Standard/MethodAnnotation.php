<?php
declare(strict_types=1);

namespace MVQN\Annotations\Standard;

use MVQN\Annotations\Annotation;

/**
 * Class MethodAnnotation
 *
 * @package MVQN\Annotations\Standard
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class MethodAnnotation extends Annotation
{
    /** @const int Denotes supported annotation targets, defaults to ANY when not explicitly provided! */
    public const SUPPORTED_TARGETS = Annotation::TARGET_CLASS;

    /** @const bool Denotes supporting multiple declarations of this annotation per block, defaults to TRUE! */
    public const SUPPORTED_DUPLICATES = true;

    /**
     * @param array $existing Any existing annotations that were previously parsed from the same declaration.
     * @return array Returns an array of keyword => value(s) parsed by this Annotation implementation.
     */
    public function parse(array $existing = []): array
    {
        $pattern = '/^\s*(static)*\s*([\w\|\[\]\_\\\]+)\s*(.*)\((.*)\)\s*(.*)$/';
        //$pattern = '/^([\w\|\[\]\_\\\]+)\s*(.*)\((.*)\)\s*(.*)$/';

        if(preg_match($pattern, $this->value, $matches))
        {
            /*
            $param = [];
            $param["types"] = explode("|", $matches[1]);
            $param["name"] = $matches[2];
            $param["args"] = $matches[3];
            $param["description"] = $matches[4];

            $existing["method"][$matches[2]] = $param;
            */
            $param = [];
            $param["static"] = ($matches[1] === "static");
            $param["types"] = explode("|", $matches[2]);
            $param["name"] = $matches[3];
            $param["args"] = $matches[4];
            $param["description"] = $matches[5];

            $existing["method"][$matches[3]] = $param;
        }

        return $existing;
    }
}
