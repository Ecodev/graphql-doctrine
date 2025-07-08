<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use Attribute;

/**
 * Attribute used to exclude a method from GraphQL fields, or a property from GraphQL filters.
 *
 * This should be used to hide sensitive data such as passwords.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class Exclude implements ApiAttribute {}
