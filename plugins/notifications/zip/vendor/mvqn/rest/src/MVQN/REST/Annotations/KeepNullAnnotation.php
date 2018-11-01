<?php
declare(strict_types=1);

namespace MVQN\REST\Annotations;

use MVQN\Annotations\Annotation;
use MVQN\Annotations\AnnotationReader;

final class KeepNullAnnotation extends Annotation
{
    /** @const int Denotes supported annotation targets, defaults to ANY when not explicitly provided! */
    public const SUPPORTED_TARGETS = Annotation::TARGET_PROPERTY;

    /** @const bool Denotes supporting multiple declarations of this annotation per block, defaults to TRUE! */
    public const SUPPORTED_DUPLICATES = false;

    /**
     * @param array $existing Any existing annotations that were previously parsed from the same declaration.
     * @return array Returns an array of keyword => value(s) parsed by this Annotation implementation.
     */
    public function parse(array $existing = []): array
    {
        $existing["KeepNull"] = true;
        return $existing;
    }
}
