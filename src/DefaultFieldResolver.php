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
    private function resolveObject($source, ?array $args, string $fieldName)
    {
        $getter = $this->getGetter($source, $fieldName);
        if ($getter) {
            $args = $this->orderArguments($getter, $args);

            return $getter->invoke($source, ...$args);
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
     * Return the getter/isser method if any valid one exists
     * @param mixed $source
     * @param string $name
     * @return string
     */
    private function getGetter($source, string $name): ?ReflectionMethod
    {
        if (!preg_match('~^(is|has)[A-Z]~', $name)) {
            $name = 'get' . ucfirst($name);
        }

        $class = new ReflectionClass($source);
        if ($class->hasMethod($name)) {
            $method = $class->getMethod($name);
            if ($method->getModifiers() & ReflectionMethod::IS_PUBLIC) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Re-order associative args to ordered args
     * @param ReflectionMethod $method
     * @param array $args
     * @return array
     */
    private function orderArguments(ReflectionMethod $method, ?array $args): array
    {
        $result = [];
        foreach ($method->getParameters() as $param) {
            if (array_key_exists($param->getName(), $args)) {
                $result[] = $args[$param->getName()];
            }
        }

        return $result;
    }
}
