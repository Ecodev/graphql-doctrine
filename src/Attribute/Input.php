<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use Attribute;

/**
 * Attribute used to override values for an input field in GraphQL.
 *
 * All values are optional and should only be used to override
 * what is declared by the original method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Input extends AbstractAttribute
{
    /**
     * @param null|string $type FQCN of PHP class implementing the GraphQL type
     */
    public function __construct(
        ?string $type = null,
        ?string $description = null,
        mixed $defaultValue = self::NO_VALUE_PASSED,
    ) {
        parent::__construct(null, $type, $description, $defaultValue);
    }
}
