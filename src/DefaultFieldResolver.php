<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use GraphQL\Type\Definition\ResolveInfo;

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
            $class = new \ReflectionClass($source);

            // If not isser, make it a getter
            $getter = $fieldName;
            if (!preg_match('~^(is|has)[A-Z]~', $getter)) {
                $getter = 'get' . ucfirst($fieldName);
            }

            if ($class->hasMethod($getter) && $class->getMethod($getter)->getModifiers() & \ReflectionMethod::IS_PUBLIC) {
                $args = (array) $args;
                $property = $source->$getter(...$args);
            } elseif (isset($source->{$fieldName})) {
                $property = $source->{$fieldName};
            }
        } elseif (is_array($source) || $source instanceof \ArrayAccess) {
            if (isset($source[$fieldName])) {
                $property = $source[$fieldName];
            }
        }

        return $property instanceof \Closure ? $property($source, $args, $context) : $property;
    }
}
