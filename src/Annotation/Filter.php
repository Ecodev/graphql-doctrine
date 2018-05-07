<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to override values for an field argument in GraphQL.
 *
 * The name of the argument is required and must match the actual PHP argument name.
 *
 * All other values are optional and should only be used to override
 * what is declared by the original argument of the method.
 *
 * @Annotation
 * @Target({"ANNOTATION"})
 */
final class Filter
{
    /**
     * Name of the field on which to apply the operator
     *
     * The field may or may not actually exist in the entity. It is merely used
     * to organize the filter correctly in the API.
     *
     *
     * @var string
     * @Required
     */
    public $field;

    /**
     * Key referring to the type instance of PHP class implementing the GraphQL type
     *
     * @var string
     * @Required
     */
    public $operator;

    /**
     * GraphQL leaf type name of the type of the field
     *
     * @var string
     * @Required
     */
    public $type;
}
