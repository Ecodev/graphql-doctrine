<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation used to define custom filters.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Filters
{
    /**
     * List of all custom filters.
     *
     * @var array<\GraphQL\Doctrine\Annotation\Filter>
     *
     * @Required
     */
    public array $filters = [];

    /**
     * @param array<Filter> $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }
}
