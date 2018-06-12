<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to define custom filters.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Filters
{
    /**
     * List of all custom filters
     *
     * @var array<\GraphQL\Doctrine\Annotation\Filter>
     * @Required
     */
    public $filters = [];
}
