<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation used to override values for a filterGroupCondition in GraphQL.
 *
 * This should only be used to override the Doctrine column type declared on the property by
 * a custom GraphQL type.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"PROPERTY"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class FilterGroupCondition
{
    /**
     * FQCN of PHP class implementing the GraphQL type.
     *
     * @Required
     */
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }
}
