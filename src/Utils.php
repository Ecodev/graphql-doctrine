<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\Common\Annotations\Reader;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\ScalarType;
use ReflectionClass;

/**
 * A few utils.
 */
abstract class Utils
{
    /**
     * Get the GraphQL type name for an output type from the PHP class.
     */
    public static function getTypeName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    /**
     * Get the GraphQL type name for a Filter type from the PHP class.
     *
     * @param EnumType|ScalarType $type
     */
    public static function getOperatorTypeName(string $className, LeafType $type): string
    {
        return preg_replace('~Type$~', '', self::getTypeName($className)) . ucfirst($type->name);
    }

    /**
     * Return an array of all annotations found in the class hierarchy, including its traits, indexed by the class name.
     *
     * @param class-string<T> $annotationName
     *
     * @return array annotations indexed by the class name where they were found
     *
     * @template T
     */
    public static function getRecursiveClassAnnotations(Reader $reader, ReflectionClass $class, string $annotationName): array
    {
        $result = [];

        $annotation = $reader->getClassAnnotation($class, $annotationName);
        if ($annotation) {
            $result[$class->getName()] = $annotation;
        }

        foreach ($class->getTraits() as $trait) {
            $result = array_merge($result, self::getRecursiveClassAnnotations($reader, $trait, $annotationName));
        }

        $parent = $class->getParentClass();
        if ($parent) {
            $result = array_merge($result, self::getRecursiveClassAnnotations($reader, $parent, $annotationName));
        }

        return $result;
    }
}
