<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

/**
 * A few utils
 */
abstract class Utils
{
    /**
     * Get the GraphQL type name from the PHP class
     * @param string $className
     * @return string
     */
    public static function getTypeName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    /**
     * Get the GraphQL type name for an ID type from the PHP class
     * @param string $className
     * @return string
     */
    public static function getIDTypeName(string $className): string
    {
        return self::getTypeName($className) . 'ID';
    }
}
