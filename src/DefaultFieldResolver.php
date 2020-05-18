<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Closure;
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
    /**
     * @param mixed $source
     * @param mixed[] $args
     * @param mixed $context
     *
     * @return null|mixed
     */
    public function __invoke($source, array $args, $context, ResolveInfo $info)
    {
        /** @var string $fieldName */
        $fieldName = $info->fieldName;
        $property = null;

        if (is_object($source)) {
            $property = $this->resolveObject($source, $args, $fieldName);
        } elseif (is_array($source)) {
            $property = $this->resolveArray($source, $fieldName);
        }

        return $property instanceof Closure ? $property($source, $args, $context) : $property;
    }

    /**
     * Resolve for an object
     *
     * @param mixed $source
     *
     * @return mixed
     */
    private function resolveObject($source, array $args, string $fieldName)
    {
        $getter = $this->getGetter($source, $fieldName);
        if ($getter) {
            $args = $this->orderArguments($getter, $args);

            return $getter->invoke($source, ...$args);
        }

        if (isset($source->{$fieldName})) {
            return $source->{$fieldName};
        }

        return null;
    }

    /**
     * Resolve for an array
     *
     * @param mixed $source
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
     */
    private function orderArguments(ReflectionMethod $method, array $args): array
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
