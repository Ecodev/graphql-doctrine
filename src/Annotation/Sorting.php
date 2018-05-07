<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to define custom sorting.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Sorting
{
    /**
     * FQCN of PHP class implementing `SortingInterface`
     *
     * @var array<string>
     */
    public $classes = [];
}
