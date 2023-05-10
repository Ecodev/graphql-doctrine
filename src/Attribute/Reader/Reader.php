<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute\Reader;

use GraphQL\Doctrine\Attribute\ApiAttribute;
use GraphQL\Doctrine\Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * API attribute reader.
 */
final class Reader
{
    /**
     * Return an array of all attributes found in the class hierarchy, including its traits, indexed by the class name.
     *
     * @template T of ApiAttribute
     *
     * @param class-string<T> $attributeName
     *
     * @return array<class-string, T[]> attributes indexed by the class name where they were found
     */
    public function getRecursiveClassAttributes(ReflectionClass $class, string $attributeName): array
    {
        $result = [];

        $attributes = $this->getAttributeInstances($class, $attributeName);
        if ($attributes) {
            $result[$class->getName()] = $attributes;
        }

        foreach ($class->getTraits() as $trait) {
            $result = array_merge($result, self::getRecursiveClassAttributes($trait, $attributeName));
        }

        $parent = $class->getParentClass();
        if ($parent) {
            $result = array_merge($result, self::getRecursiveClassAttributes($parent, $attributeName));
        }

        return $result;
    }

    /**
     * @template T of ApiAttribute
     *
     * @param class-string<T> $attributeName
     *
     * @return null|T
     */
    public function getAttribute(ReflectionClass|ReflectionProperty|ReflectionMethod|ReflectionParameter $element, string $attributeName): ?ApiAttribute
    {
        $attributes = $this->getAttributeInstances($element, $attributeName);

        return reset($attributes) ?: null;
    }

    /**
     * @template T of ApiAttribute
     *
     * @param class-string<T> $attributeName
     *
     * @return T[]
     */
    private function getAttributeInstances(ReflectionClass|ReflectionMethod|ReflectionParameter|ReflectionProperty $element, string $attributeName): array
    {
        if (!is_subclass_of($attributeName, ApiAttribute::class)) {
            throw new Exception(self::class . ' cannot be used for attribute than are not part of `ecodev/graphql-doctrine`.');
        }

        $attributes = $element->getAttributes($attributeName);
        $instances = [];

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            assert($instance instanceof ApiAttribute);

            $instances[] = $instance;
        }

        return $instances;
    }
}
