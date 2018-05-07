<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Doctrine\Exception;
use GraphQL\Doctrine\Factory\AbstractFactory;
use GraphQL\Type\Definition\Type;
use ReflectionClass;

/**
 * A factory to create an ObjectType from a Doctrine entity
 */
abstract class AbstractTypeFactory extends AbstractFactory
{
    /**
     * Create an ObjectType from a Doctrine entity
     *
     * @param string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     *
     * @return Type
     */
    abstract public function create(string $className, string $typeName): Type;

    /**
     * Get the description of a class from the doc block
     *
     * @param string $className
     *
     * @return null|string
     */
    final protected function getDescription(string $className): ?string
    {
        $class = new \ReflectionClass($className);

        $comment = $class->getDocComment();

        // Remove the comment markers
        $comment = preg_replace('~^\s*(/\*\*|\* ?|\*/)~m', '', $comment);

        // Keep everything before the first annotation
        $comment = trim(explode('@', $comment)[0]);

        if (!$comment) {
            $comment = null;
        }

        return $comment;
    }

    /**
     * Throw an exception if the given type does not inherit expected type
     *
     * @param ReflectionClass $class
     * @param string $annotation
     * @param string $expected
     * @param string $className
     */
    final protected function throwIfInvalidAnnotation(ReflectionClass $class, string $annotation, string $expected, string $className): void
    {
        if (!is_a($className, $expected, true)) {
            throw new Exception('On class `' . $class->getName() . '` the annotation `@API\\' . $annotation . '` expects a FQCN implementing `' . $expected . '`, but instead got: ' . $className);
        }
    }
}
