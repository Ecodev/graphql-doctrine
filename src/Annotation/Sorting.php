<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use Attribute;
use GraphQL\Doctrine\Sorting\SortingInterface;

/**
 * Annotation used to define custom sorting.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Sorting
{
    /**
     * FQCN of PHP class implementing `SortingInterface`.
     *
     * @var array<string>
     */
    public array $classes = [];

    /**
     * @param array<string> $classes
     */
    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }
}
