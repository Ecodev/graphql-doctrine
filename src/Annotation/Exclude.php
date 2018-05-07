<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to exclude a method from GraphQL fields.
 *
 * This should be used to hide sensitive data such as passwords.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class Exclude
{
}
