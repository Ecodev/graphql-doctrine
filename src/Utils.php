<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ScalarType;

/**
 * A few utils.
 */
abstract class Utils
{
    /**
     * Get the GraphQL type name for an output type from the PHP class.
     *
     * @param class-string $className
     */
    public static function getTypeName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    /**
     * Get the GraphQL type name for a Filter type from the PHP class.
     *
     * @param class-string $className
     */
    public static function getOperatorTypeName(string $className, EnumType|ScalarType $type): string
    {
        return preg_replace('~Type$~', '', self::getTypeName($className)) . ucfirst($type->name);
    }
}
