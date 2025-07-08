<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use Attribute;
use GraphQL\Doctrine\Sorting\SortingInterface;

/**
 * Attribute used to define custom sorting.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Sorting implements ApiAttribute
{
    /**
     * @param class-string<SortingInterface> $class FQCN of PHP class implementing `SortingInterface`
     */
    public function __construct(
        public readonly string $class,
    ) {}
}
