<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to override values for a filterGroupCondition in GraphQL.
 *
 * This should only be used to override the Doctrine column type declared on the property by
 * a custom GraphQL type.
 *
 * @Annotation
 *
 * @Target({"PROPERTY"})
 */
final class FilterGroupCondition
{
    /**
     * FQCN of PHP class implementing the GraphQL type.
     *
     * @var string
     *
     * @Required
     */
    public $type;
}
