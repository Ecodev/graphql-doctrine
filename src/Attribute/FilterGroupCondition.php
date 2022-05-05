<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use Attribute;

/**
 * Attribute used to override values for a filterGroupCondition in GraphQL.
 *
 * This should only be used to override the Doctrine column type declared on the property by
 * a custom GraphQL type.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class FilterGroupCondition implements ApiAttribute
{
    /**
     * @param string $type FQCN of PHP class implementing the GraphQL type
     */
    public function __construct(public readonly string $type)
    {
    }
}
