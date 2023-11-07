<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use Attribute;

/**
 * Attribute used to override values for a field argument in GraphQL.
 *
 * All other values are optional and should only be used to override
 * what is declared by the original argument of the method.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Argument extends AbstractAttribute
{
    /**
     * @param null|string $type FQCN of PHP class implementing the GraphQL type, see README.md#type-syntax
     */
    public function __construct(
        ?string $type = null,
        ?string $description = null,
        mixed $defaultValue = self::NO_VALUE_PASSED,
    ) {
        parent::__construct(null, $type, $description, $defaultValue);
    }
}
