<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use Attribute;

/**
 * Attribute used to define custom filter.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Filter implements ApiAttribute
{
    /**
     * @param string $field Name of the field on which to apply the operator.
     *
     * The field may or may not actually exist in the entity. It is merely used
     * to organize the filter correctly in the API.
     * @param string $operator key referring to the type instance of PHP class implementing the GraphQL type
     * @param string $type GraphQL leaf type name of the type of the field
     */
    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        public readonly string $type,
    ) {
    }
}
