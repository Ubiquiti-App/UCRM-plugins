<?php
declare(strict_types=1);

namespace MVQN\Annotations\Standard;

use MVQN\Annotations\Annotation;

/**
 * Class AuthorAnnotation
 *
 * @package MVQN\Annotations\Standard
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class AuthorAnnotation extends Annotation
{
    /** @const int Denotes supported annotation targets, defaults to ANY when not explicitly provided! */
    public const SUPPORTED_TARGETS = Annotation::TARGET_CLASS | Annotation::TARGET_METHOD;

    /** @const bool Denotes supporting multiple declarations of this annotation per block, defaults to TRUE! */
    public const SUPPORTED_DUPLICATES = false;

    /**
     * @param array $existing Any existing annotations that were previously parsed from the same declaration.
     * @return array Returns an array of keyword => value(s) parsed by this Annotation implementation.
     */
    public function parse(array $existing = []): array
    {
        $pattern = '/^(.+)\s(?:\<(.*)\>)?$/';

        if(preg_match($pattern, $this->value, $matches))
        {
            $existing["author"]["name"] = $matches[1];
            $existing["author"]["email"] = $matches[2];
        }
        else
        {
            $existing["author"]["name"] = $this->value;
        }

        return $existing;
    }
}
