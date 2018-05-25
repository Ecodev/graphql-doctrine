<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use ArrayAccess;
use Closure;
use Doctrine\Common\Util\Inflector;
use GraphQL\Doctrine\Definition\EntityID;
use GraphQL\Type\Definition\ResolveInfo;
use ReflectionClass;
use ReflectionMethod;

/**
 * A field resolver that will allow access to public properties and getter.
 * Arguments, if any, will be forwarded as is to the method.
 */
final class DefaultFieldResolver
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
     *
     * @param mixed $source
     * @param mixed $args
     * @param string $fieldName
     *
     * @return mixed
     */
    private function resolveObject($source, ?array $args, string $fieldName)
    {
        $getter = $this->getGetter($source, $fieldName);
        if ($getter) {
            $args = $this->orderArguments($getter, $args);

            return $getter->invoke($source, ...$args);
        }

        return $source->{$fieldName} ?? null;
    }

    /**
     * Resolve for an array
     *
     * @param mixed $source
     * @param string $fieldName
     *
     * @return mixed
     */
    private function resolveArray($source, string $fieldName)
    {
        return $source[$fieldName] ?? null;
    }

    /**
     * Return the getter/isser method if any valid one exists
     *
     * @param mixed $source
     * @param string $name
     *
     * @return null|ReflectionMethod
     */
    private function getGetter($source, string $fieldName): ?ReflectionMethod
    {
        $methodName = null;
        // Note get_class_methods will only return public methods in this scope
        $methods = get_class_methods($source);
        $class = new ReflectionClass($source);

        if (!preg_match('~^(is|has)[A-Z]~', $fieldName)) {
            $getter = 'get' . Inflector::classify($fieldName);
            $isser = 'is' . Inflector::classify($fieldName);
        } else {
            $getter = $isser = $fieldName;
        }

        if (in_array($getter, $methods, true)) {
            $methodName = $getter;
        } elseif (in_array($isser, $methods, true)) {
            $methodName = $isser;
        } elseif (mb_substr($fieldName, 0, 2) === 'is'
            && ctype_upper(mb_substr($fieldName, 2, 1))
            && in_array($fieldName, $methods, true)
        ) {
            $methodName = $fieldName;
        }

        return $methodName && $class->hasMethod($methodName)
            ? $class->getMethod($methodName)
            : null;
    }

    /**
     * Re-order associative args to ordered args
     *
     * @param ReflectionMethod $method
     * @param array $args
     *
     * @return array
     */
    private function orderArguments(ReflectionMethod $method, ?array $args): array
    {
        $result = [];
        if (!$args) {
            return $result;
        }

        foreach ($method->getParameters() as $param) {
            if (array_key_exists($param->getName(), $args)) {
                $arg = $args[$param->getName()];

                // Fetch entity from DB
                if ($arg instanceof EntityID) {
                    $arg = $arg->getEntity();
                }

                $result[] = $arg;
            }
        }

        return $result;
    }
}
