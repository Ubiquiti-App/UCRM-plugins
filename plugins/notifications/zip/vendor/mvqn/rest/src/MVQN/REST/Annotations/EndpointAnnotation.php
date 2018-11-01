<?php
declare(strict_types=1);

namespace MVQN\REST\Annotations;

use MVQN\Annotations\Annotation;
use MVQN\Annotations\AnnotationReader;
use MVQN\Common\Arrays;
use MVQN\Common\Patterns;

final class EndpointAnnotation extends Annotation
{

    /** @const int Denotes supported annotation targets, defaults to ANY when not explicitly provided! */
    public const SUPPORTED_TARGETS = Annotation::TARGET_CLASS;

    /** @const bool Denotes supporting multiple declarations of this annotation per block, defaults to TRUE! */
    public const SUPPORTED_DUPLICATES = true;

    /**
     * @param array $existing Any existing annotations that were previously parsed from the same declaration.
     * @return array Returns an array of keyword => value(s) parsed by this Annotation implementation.
     * @throws \Exception
     */
    public function parse(array $existing = []): array
    {
        if(Patterns::isJSON($this->value) || Patterns::isArray($this->value))
        {
            $existing = Arrays::combineResults($existing, "Endpoint", $this->value, Arrays::COMBINE_MODE_MERGE);
        }

        return $existing;
    }
}
