<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use ArrayAccess;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use ReflectionClass;
use ReflectionMethod;

/**
 * A field resolver that will allow access to public properties and getter.
 * Arguments, if any, will be forwarded as is to the method.
 */
class DefaultFieldResolver
{
    public function __invoke($source, $args, $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $property = null;

        if (is_object($source)) {
            $property = $this->resolveObject($source, $args, $fieldName);
        } elseif (is_array($source) || $source instanceof ArrayAccess) {
            $property = $this->resolveArray($source, $fieldName);
        }

        return $property instanceof Closure ? $property($source, $args, $context) : $property;
    }

    /**
     * Resolve for an object
     * @param mixed $source
     * @param mixed $args
     * @param string $fieldName
     * @return mixed
     */
    private function resolveObject($source, $args, string $fieldName)
    {
        $class = new ReflectionClass($source);
        $getter = $this->getGetter($fieldName);

        if ($class->hasMethod($getter) && $class->getMethod($getter)->getModifiers() & ReflectionMethod::IS_PUBLIC) {
            $args = (array) $args;

            return $source->$getter(...$args);
        } elseif (isset($source->{$fieldName})) {
            return $source->{$fieldName};
        }

        return null;
    }

    /**
     * Resolve for an array
     * @param mixed $source
     * @param string $fieldName
     * @return mixed
     */
    private function resolveArray($source, string $fieldName)
    {
        return $source[$fieldName] ?? null;
    }

    /**
     * If not isser, make it a getter
     * @param string $fieldName
     * @return string
     */
    private function getGetter(string $fieldName): string
    {
        if (preg_match('~^(is|has)[A-Z]~', $fieldName)) {
            return $fieldName;
        }

        return 'get' . ucfirst($fieldName);
    }
}
