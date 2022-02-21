<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use GraphQL\Doctrine\Annotation\Exclude;
use GraphQL\Doctrine\Exception;
use GraphQL\Doctrine\Factory\MetadataReader\MappingDriverChainAdapter;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\Type;
use ReflectionClass;
use ReflectionProperty;

/**
 * Abstract factory to be aware of types and entityManager.
 */
abstract class AbstractFactory
{
    public function __construct(protected Types $types, protected EntityManager $entityManager)
    {
    }

    /**
     * Get annotation reader.
     */
    final protected function getAnnotationReader(): Reader
    {
        $driver = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
        if ($driver instanceof AnnotationDriver) {
            return $driver->getReader();
        }

        if ($driver instanceof MappingDriverChain) {
            return new MappingDriverChainAdapter($driver);
        }

        throw new Exception('graphql-doctrine requires Doctrine to be configured with a `' . AnnotationDriver::class . '`.');
    }

    /**
     * Returns whether the property is excluded.
     */
    final protected function isPropertyExcluded(ReflectionProperty $property): bool
    {
        $exclude = $this->getAnnotationReader()->getPropertyAnnotation($property, Exclude::class);

        return $exclude !== null;
    }

    /**
     * Get instance of GraphQL type from a PHP class name.
     *
     * Supported syntaxes are the following:
     *
     *  - `?MyType`
     *  - `null|MyType`
     *  - `MyType|null`
     *  - `MyType[]`
     *  - `?MyType[]`
     *  - `null|MyType[]`
     *  - `MyType[]|null`
     */
    final protected function getTypeFromPhpDeclaration(ReflectionClass $class, ?string $typeDeclaration, bool $isEntityId = false): ?Type
    {
        if (!$typeDeclaration) {
            return null;
        }

        $isNullable = 0;
        $name = preg_replace('~(^\?|^null\||\|null$)~', '', $typeDeclaration, -1, $isNullable);

        $isList = 0;
        $name = preg_replace('~^(.*)\[\]$~', '$1', $name, -1, $isList);
        $name = $this->adjustNamespace($class, $name);
        $type = $this->getTypeFromRegistry($name, $isEntityId);

        if ($isList) {
            $type = Type::listOf(Type::nonNull($type));
        }

        if (!$isNullable) {
            $type = Type::nonNull($type);
        }

        return $type;
    }

    /**
     * Prepend namespace of the method if the class actually exists.
     */
    private function adjustNamespace(ReflectionClass $class, string $type): string
    {
        if ($type === 'self') {
            $type = $class->getName();
        }

        $namespace = $class->getNamespaceName();
        if ($namespace) {
            $namespacedType = $namespace . '\\' . $type;

            if (class_exists($namespacedType)) {
                return $namespacedType;
            }
        }

        return $type;
    }

    /**
     * Returns a type from our registry.
     */
    final protected function getTypeFromRegistry(string $type, bool $isEntityId): Type
    {
        if ($this->types->isEntity($type) && $isEntityId) {
            // @phpstan-ignore-next-line
            return $this->types->getId($type);
        }

        if ($this->types->isEntity($type) && !$isEntityId) {
            // @phpstan-ignore-next-line
            return $this->types->getOutput($type);
        }

        return $this->types->get($type);
    }
}
