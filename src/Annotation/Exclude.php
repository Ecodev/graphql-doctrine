<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to exclude a method from GraphQL fields, or a property from GraphQL filters.
 *
 * This should be used to hide sensitive data such as passwords.
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
final class Exclude
{
}
